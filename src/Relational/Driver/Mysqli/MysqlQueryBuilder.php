<?php
namespace Vda\Datasource\Relational\Driver\Mysqli;

use Vda\Datasource\Relational\QueryBuilder;
use Vda\Query\Operator\JsonGet;
use Vda\Query\Operator\Operator;
use Vda\Query\Select;
use Vda\Query\Upsert;
use Vda\Util\Type;

class MysqlQueryBuilder extends QueryBuilder
{
    public function processUpsertQuery(Upsert $upsert)
    {
        if (!$this->currentState->isRoot()) {
            throw new \RuntimeException("Upsert query must be a root object");
        }

        $this->enterState($this->stateFactory->upsert());

        $t = $upsert->getTable();

        $this->query = 'INSERT INTO ';

        $t->onProcess($this);

        $this->query .= ' (';
        $this->buildExpressions($upsert->getInsertFields(), ', ');
        $this->query .= ') ';

        $this->query .= ' VALUES (';
        $this->buildExpressions($upsert->getInsertValues(), ', ');
        $this->query .= ') ';

        $this->query .= ' ON DUPLICATE KEY UPDATE ';
        $updateFields = $upsert->getUpdateFields();
        $updateValues = $upsert->getUpdateValues();

        $keyFields = $t->_primaryKey->getFields();

        if (!empty($t->_primaryKey) && \count($keyFields) == 1) {
            $pk = $t->getField(\reset($keyFields));
            if ($pk->getType() == Type::INTEGER && !\in_array($pk, $updateFields)) {
                $updateFields[] = $pk;
                $updateValues[] = Operator::call('LAST_INSERT_ID', [$pk]);
            }
        }

        $this->buildUpdateList($updateFields, $updateValues);

        $this->leaveState();
    }

    public function processJsonGet(JsonGet $get)
    {
        $this->query .= 'JSON_UNQUOTE(JSON_EXTRACT(';
        $get->getDoc()->onProcess($this);
        $this->query .= ', ' . $this->dialect->quote('$.' . \ltrim($get->getPath(), '.'), Type::STRING) . '))';
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
                $this->query .= ' LOCK IN SHARE MODE';
                break;
            default:
                throw new \RuntimeException("Invalid lock mode #{$mode}");
        }
    }
}
