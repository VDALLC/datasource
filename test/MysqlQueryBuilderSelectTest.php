<?php
use Vda\Datasource\Relational\Driver\Mysqli\MysqlConnection;
use Vda\Datasource\Relational\IQueryBuilder;
use Vda\Query\Field;
use Vda\Query\Select;
use Vda\Query\Table;
use Vda\Util\Type;

class DTest extends Table
{
    public $id;
    public $value;

    public function __construct()
    {
        $this->id = new Field(Type::INTEGER);
        $this->value = new Field(Type::STRING);

        parent::__construct('test', 'test');
    }
}

class MysqlQueryBuilderSelectTestClass extends PHPUnit_Framework_TestCase
{
    /**
     * @var IQueryBuilder
     */
    private $queryBuilder;

    /**
     * @var DTest
     */
    private $dTest;

    public function setUp()
    {
        $this->queryBuilder = (new MysqlConnection('mysql://root@localhost/test'))->getQueryBuilder();
        $this->dTest = new DTest();
    }

    public function testSelectFromTable()
    {
        $query = Select::select()
            ->from($this->dTest);

        $this->assertEquals(
            'SELECT `test`.`id`, `test`.`value` FROM `test` AS `test`',
            $this->queryBuilder->build($query)
        );
    }

    public function testSelectFromTableWhere()
    {
        $query = Select::select()
            ->from($this->dTest)
            ->where($this->dTest->id->eq(1));

        $this->assertEquals(
            'SELECT `test`.`id`, `test`.`value` FROM `test` AS `test` WHERE `test`.`id`=1',
            $this->queryBuilder->build($query)
        );
    }

    public function testSelectFromTableWhereForUpdate()
    {
        $query = Select::select()
            ->from($this->dTest)
            ->where($this->dTest->id->eq(1))
            ->forUpdate();

        $this->assertEquals(
            'SELECT `test`.`id`, `test`.`value` FROM `test` AS `test` WHERE `test`.`id`=1 FOR UPDATE',
            $this->queryBuilder->build($query)
        );
    }

    public function testSelectFromTableWhereForShare()
    {
        $query = Select::select()
            ->from($this->dTest)
            ->where($this->dTest->id->eq(1))
            ->forShare();

        $this->assertEquals(
            'SELECT `test`.`id`, `test`.`value` FROM `test` AS `test` WHERE `test`.`id`=1 LOCK IN SHARE MODE',
            $this->queryBuilder->build($query)
        );
    }
}
