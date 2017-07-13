<?php
namespace Vda\Datasource\Relational;

use Vda\Datasource\Relational\Driver\ISqlDialect;
use Vda\Query\Alias;
use Vda\Query\Delete;
use Vda\Query\Field;
use Vda\Query\IExpression;
use Vda\Query\IQueryPart;
use Vda\Query\IQueryProcessor;
use Vda\Query\Insert;
use Vda\Query\JoinClause;
use Vda\Query\Order;
use Vda\Query\Select;
use Vda\Query\Table;
use Vda\Query\Update;
use Vda\Query\Upsert;
use Vda\Query\Operator\BinaryOperator;
use Vda\Query\Operator\CompositeOperator;
use Vda\Query\Operator\Constant;
use Vda\Query\Operator\FunctionCall;
use Vda\Query\Operator\Mask;
use Vda\Query\Operator\Operator;
use Vda\Query\Operator\UnaryOperator;
use Vda\Util\Type;

class QueryBuilder implements IQueryBuilder, IQueryProcessor
{
    protected static $patternSubstitute = array(
        '\\\\\\\\' => '\\\\',
        '\\\\?' => '?',
        '\\\\*' => '*',
        '?' => '_',
        '*' => '%'
    );

    protected static $opcodes = array(
        Operator::MNEMONIC_COMPOSITE_AND => ' AND ',
        Operator::MNEMONIC_COMPOSITE_OR  => ' OR ',
        Operator::MNEMONIC_COMPOSITE_PLUS => ' + ',
        Operator::MNEMONIC_COMPOSITE_MULTIPLY => ' * '
    );

    /**
     * @var ISqlDialect
     */
    protected $dialect;

    /**
     * @var QueryBuilderStateFactory
     */
    protected $stateFactory;
    /**
     * @var QueryBuilderState[]
     */
    protected $stateStack;

    /**
     * @var QueryBuilderState
     */
    protected $currentState;

    protected $sourceGlueStack;
    protected $currentSourceGlue;

    /**
     * @var string
     */
    protected $query;

    public function __construct(ISqlDialect $dialect, QueryBuilderStateFactory $stateFactory)
    {
        $this->dialect = $dialect;
        $this->stateFactory = $stateFactory;
    }

    public function build(IQueryPart $processable)
    {
        $this->query = '';
        $this->stateStack = array();
        $this->currentState = $this->stateFactory->root();
        $this->sourceGlueStack = array();
        $this->currentSourceGlue = '';

        $processable->onProcess($this);

        return $this->query;
    }

    public function processSelectQuery(Select $select)
    {
        if ($this->currentState->isSelectParenthesized()) {
            $this->query .= '(';
        }

        $this->enterState($this->stateFactory->select());

        $this->query .= 'SELECT ';

        $this->buildExpressions($select->getFields(), ', ');
        $this->buildSources(' FROM ', $select->getSources());
        $this->buildCriteria(' WHERE ', $select->getCriteria());

        $this->enterState($this->stateFactory->groupOrderClause());
        $this->buildGroups($select->getGroups());
        $this->buildOrders($select->getOrders());
        $this->leaveState();

        $this->buildLimits($select->getLimit(), $select->getOffset());
        $this->buildLockMode($select->getLockMode());

        $this->leaveState();

        if ($this->currentState->isSelectParenthesized()) {
            $this->query .= ')';
        }
    }

    public function processInsertQuery(Insert $insert)
    {
        if (!$this->currentState->isRoot()) {
            throw new \RuntimeException("Insert query must be a root object");
        }

        $this->enterState($this->stateFactory->insert());

        $this->query = 'INSERT INTO ';

        $insert->getTable()->onProcess($this);

        if ($insert->hasFields()) {
            $this->query .= ' (';
            $this->buildExpressions($insert->getFields(), ', ');
            $this->query .= ') ';
        }

        if ($insert->isFromSelect()) {
            $insert->getSelect()->onProcess($this);
        } else {
            $this->query .= ' VALUES (';
            $currentGlue = '';
            foreach ($insert->getValues() as $values) {
                $this->query .= $currentGlue;
                $this->buildExpressions($values, ', ');
                $currentGlue = '), (';
            }
            $this->query .= ') ';
        }

        $this->leaveState();
    }

    public function processUpdateQuery(Update $update)
    {
        if (!$this->currentState->isRoot()) {
            throw new \RuntimeException("Update query must be a root object");
        }

        $this->enterState($this->stateFactory->update());

        $this->query = 'UPDATE ';

        $this->buildSources('', $update->getTables());

        $this->query .= ' SET ';

        $this->buildUpdateList($update->getFields(), $update->getExpressions());

        $this->buildCriteria(' WHERE ', $update->getCriteria());

        $this->leaveState();
    }

    public function processUpsertQuery(Upsert $merge)
    {
        if (!$this->currentState->isRoot()) {
            throw new \RuntimeException("Upsert query must be a root object");
        }

        $this->enterState($this->stateFactory->upsert());

        $this->query = 'MERGE INTO ';

        $merge->getTable()->onProcess($this);

        $this->query .= ' USING DUAL ON ';

        $merge->getCriteria()->onProcess($this);

        $this->query .= ' WHEN MATCHED THEN UPDATE ';

        $this->buildUpdateList($merge->getUpdateFields(), $merge->getUpdateValues());

        $this->query .= ' WHEN NOT MATCHED THEN INSERT ';

        $this->query .= ' (';
        $this->buildExpressions($merge->getInsertFields(), ', ');
        $this->query .= ') ';

        $this->query .= ' VALUES (';
        $this->buildExpressions($merge->getInsertValues(), ', ');
        $this->query .= ') ';

        $this->leaveState();
    }

    public function processDeleteQuery(Delete $delete)
    {
        if (!$this->currentState->isRoot()) {
            throw new \RuntimeException("Delete query must be a root object");
        }

        $this->enterState($this->stateFactory->delete());

        $this->query .= 'DELETE ';

        $this->buildSources(' FROM ', $delete->getTables());

        $this->buildCriteria(' WHERE ', $delete->getCriteria());

        $this->leaveState();
    }

    public function processField(Field $field)
    {
        if (!$this->currentState->isScopeIgnored() && $field->getScope() !== null) {

            $scope = $this->currentState->isAliasIgnored()
                ? $field->getScope()->getName()
                : $field->getScope()->getAlias();

            $this->query .= $this->dialect->quoteIdentifier($scope) . '.';
        }

        $this->query .= $this->dialect->quoteIdentifier($field->getName());
    }

    public function processTable(Table $table)
    {
        $this->query .= $this->currentSourceGlue;

        $schema = $table->getSchema();
        if (!empty($schema)) {
            $this->query .= $this->dialect->quoteIdentifier($schema) . '.';
        }

        $this->query .= $this->dialect->quoteIdentifier($table->getName());

        if (!$this->currentState->isAliasIgnored()) {
            $this->query .= ' AS ' . $this->dialect->quoteIdentifier($table->getAlias());
        }
    }

    public function processJoin(JoinClause $join)
    {
        $this->currentSourceGlue = '';

        switch ($join->getType()) {
            case JoinClause::TYPE_INNER:
                $this->query .= ' JOIN ';
                break;
            case JoinClause::TYPE_LEFT:
                $this->query .= ' LEFT JOIN ';
                break;
            default:
                throw new \InvalidArgumentException("Unsupported join type: {$join->getType()}");
        }

        $join->getTarget()->onProcess($this);

        if ($join->getCriterion() !== null) {
            $this->buildCriteria(' ON ', $join->getCriterion());
        }
    }

    public function processUnaryOperator(UnaryOperator $op)
    {
        $operand = $op->getOperand();

        switch ($op->getMnemonic()) {
            case Operator::MNEMONIC_UNARY_NOT:
                $this->query .= 'NOT (';
                $operand->onProcess($this);
                $this->query .= ')';
                break;

            case Operator::MNEMONIC_UNARY_ISNULL:
                $operand->onProcess($this);
                $this->query .= ' IS NULL';
                break;

            case Operator::MNEMONIC_UNARY_NOTNULL:
                $operand->onProcess($this);
                $this->query .= ' IS NOT NULL';
                break;

            default:
                $this->onInvalidMnemonic('unary', $op->getMnemonic());
        }
    }

    public function processBinaryOperator(BinaryOperator $op)
    {
        $matchOperators = array(
            Operator::MNEMONIC_BINARY_MATCH,
            Operator::MNEMONIC_BINARY_MATCHI,
            Operator::MNEMONIC_BINARY_NOTMATCH,
            Operator::MNEMONIC_BINARY_NOTMATCHI
        );

        $opcodes = array(
            Operator::MNEMONIC_BINARY_MINUS     => '-',
            Operator::MNEMONIC_BINARY_DIVIDE    => '/',
            Operator::MNEMONIC_BINARY_EQ        => '=',
            Operator::MNEMONIC_BINARY_NEQ       => '<>',
            Operator::MNEMONIC_BINARY_GT        => '>',
            Operator::MNEMONIC_BINARY_GTE       => '>=',
            Operator::MNEMONIC_BINARY_LT        => '<',
            Operator::MNEMONIC_BINARY_LTE       => '<=',
            Operator::MNEMONIC_BINARY_INSET     => ' IN ',
            Operator::MNEMONIC_BINARY_NOTINSET  => ' NOT IN ',
            Operator::MNEMONIC_BINARY_MATCH     => $this->dialect->patternMatchOperator(true, false),
            Operator::MNEMONIC_BINARY_MATCHI    => $this->dialect->patternMatchOperator(false, false),
            Operator::MNEMONIC_BINARY_NOTMATCH  => $this->dialect->patternMatchOperator(true, true),
            Operator::MNEMONIC_BINARY_NOTMATCHI => $this->dialect->patternMatchOperator(false, true),
        );

        if (empty($opcodes[$op->getMnemonic()])) {
            $this->onInvalidMnemonic('binary', $op->getMnemonic());
        }

        $op->getOperand1()->onProcess($this);
        $this->query .= $opcodes[$op->getMnemonic()];

        if (in_array($op->getMnemonic(), $matchOperators)) {
            $this->enterState($this->stateFactory->matchOperator());
            $op->getOperand2()->onProcess($this);
            $this->leaveState();
        } else {
            $op->getOperand2()->onProcess($this);
        }
    }

    public function processFunctionCall(FunctionCall $func)
    {
        //TODO Quote function name?
        $this->query .= $func->getName() . '(';
        $args = $func->getArgs();

        if (strcasecmp($func->getName(), 'count') == 0 && empty($args)) {
            $this->query .= '*';
        } else {
            $this->buildExpressions($args, ', ');
        }

        $this->query .= ')';
    }

    public function processCompositeOperator(CompositeOperator $op)
    {
        if (empty(self::$opcodes[$op->getMnemonic()])) {
            $this->onInvalidMnemonic('composite', $op->getMnemonic());
        }

        $this->query .= '(';

        $this->buildExpressions($op->getOperands(), self::$opcodes[$op->getMnemonic()]);

        $this->query .= ')';
    }

    public function processConstant(Constant $const)
    {
        if ($const->getMnemonic() != Operator::MNEMONIC_CONST) {
            $this->onInvalidMnemonic('constant', $const->getMnemonic());
        }

        $value = $const->getValue();

        if (is_null($value)) {
            $this->query .= 'NULL';
            return;
        }

        $isArray = ($const->getType() & Type::COLLECTION) > 0;
        $type = $const->getType() & ~Type::COLLECTION;

        if ($isArray) {
            $this->query .= '(';
            $glue = '';

            foreach ($value as $v) {
                $this->query .= $glue;
                $this->renderValue($v, $type);
                $glue = ', ';
            }

            $this->query .= ')';
        } else {
            $this->renderValue($value, $type);
        }
    }

    public function processMask(Mask $mask)
    {
        if ($mask->getMnemonic() != Operator::MNEMONIC_CONST) {
            $this->onInvalidMnemonic('mask', $mask->getMnemonic());
        }

        $value = $this->dialect->quoteWildcards(
            $this->dialect->quote($mask->getMask(), Type::STRING)
        );

        $this->query .= strtr($value, self::$patternSubstitute);
    }

    public function processAlias(Alias $alias)
    {
        if (!$this->currentState->isAliasPreferred()) {
            $alias->getExpression()->onProcess($this);

            if (!$this->currentState->isAliasIgnored()) {
                $this->query .= ' AS ';
            }
        }

        if (!$this->currentState->isAliasIgnored()) {
            $this->query .= $this->dialect->quoteIdentifier($alias->getAlias());
        }
    }

    public function processOrder(Order $order)
    {
        $order->getProperty()->onProcess($this);
        $this->query .= ' ' . $order->getDirection();
    }

    protected function onInvalidMnemonic($type, $mnemonic)
    {
        throw new \UnexpectedValueException(
            "Unsupported {$type} operator mnemonic: '{$mnemonic}'"
        );
    }

    protected function buildExpressions($expressions, $glue)
    {
        $currentGlue = '';
        foreach ($expressions as $e) {
            $this->query .= $currentGlue;
            $e->onProcess($this);
            $currentGlue = $glue;
        }
    }

    protected function buildSources($prefix, $sources)
    {
        if (!empty($sources)) {
            array_push($this->sourceGlueStack, $this->currentSourceGlue);
            $this->currentSourceGlue = '';

            $this->query .= $prefix;

            foreach ($sources as $source) {
                $source->onProcess($this);
                $this->currentSourceGlue = ', ';
            }

            $this->currentSourceGlue = array_pop($this->sourceGlueStack);
        }
    }

    protected function buildUpdateList($fields, $expressions)
    {
        $glue = '';
        foreach ($fields as $i => $f) {
            $this->query .= $glue;
            $f->onProcess($this);
            $this->query .= '=';
            $expressions[$i]->onProcess($this);
            $glue = ', ';
        }
    }

    protected function buildCriteria($prefix, $criteria)
    {
        if ($criteria !== null) {
            $this->query .= $prefix;
            $criteria->onProcess($this);
        }
    }

    protected function buildGroups($groups)
    {
        if (!empty($groups)) {
            $this->query .= ' GROUP BY ';
            $this->buildExpressions($groups, ', ');
        }
    }

    protected function buildOrders($orders)
    {
        if (!empty($orders)) {
            $this->query .= ' ORDER BY ';
            $this->buildExpressions($orders, ', ');
        }
    }

    protected function buildLimits($limit, $offset)
    {
        if ($limit !== null) {
            $this->query .= ' ' . $this->dialect->limitClause($limit, $offset);
        }
    }

    protected function buildLockMode($mode)
    {
        switch ($mode) {
            case Select::LOCK_NONE:
                break;
            case Select::LOCK_FOR_UPDATE:
                $this->query .= ' FOR UPDATE';
                break;
            case Select::LOCK_FOR_SHARE:
                $this->query .= ' FOR SHARE';
                break;
            default:
                throw new \RuntimeException("Invalid lock mode #{$mode}");
        }
    }

    protected function renderValue($value, $type)
    {
        if ($value instanceof IExpression) {
            $value->onProcess($this);
        } else {
            if ($type == Type::AUTO) {
                $type = Type::resolveType($value);
            }

            $value = $this->dialect->quote($value, $type);

            if ($this->currentState->isWildcardsQuoted()) {
                $value = $this->dialect->quoteWildcards($value);
            }

            $this->query .= $value;
        }
    }

    protected function enterState(QueryBuilderState $state)
    {
        array_push($this->stateStack, $this->currentState);
        $this->currentState = $state;
    }

    protected function leaveState()
    {
        $this->currentState = array_pop($this->stateStack);
    }
}
