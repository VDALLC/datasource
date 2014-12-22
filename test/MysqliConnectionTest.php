<?php
use Vda\Datasource\Relational\Driver\Mysqli\MysqlConnection;
use Vda\Util\Type;

class MysqliConnectionTestClass extends PHPUnit_Framework_TestCase
{
    public function testMysqli()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test');
        $this->assertTrue($conn->isConnected());

        $res = $conn->query('SHOW VARIABLES');
        $this->assertGreaterThan(0, $res->numRows());
    }

    public function testMysqliDialectOperabilityAfterReconnect()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test');
        $dialect = $conn->getDialect();
        $this->assertEquals("'qwe\\'asd'", $dialect->quote('qwe\'asd', Type::STRING));

        $conn->disconnect();
        $conn->connect();

        $this->assertEquals("'qwe\\'asd'", $dialect->quote('qwe\'asd', Type::STRING));
    }

    public function testPersistentConnection()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test?persistent=1');

        $ref = new ReflectionClass($conn);
        $prop = $ref->getProperty('mysql');
        $prop->setAccessible(true);
        $first = $prop->getValue($conn)->thread_id;

        $conn->disconnect();
        $conn->connect();
        $second = $prop->getValue($conn)->thread_id;

        $this->assertEquals($first, $second);
    }

    public function testMysqlPersistableChange()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test?persistent=1');

        $ref = new ReflectionClass($conn);
        $prop = $ref->getProperty('mysql');
        $prop->setAccessible(true);
        $first = $prop->getValue($conn)->thread_id;

        $conn->disconnect();
        $conn->setPersistable(false);
        $conn->connect();
        $second = $prop->getValue($conn)->thread_id;

        $this->assertNotEquals($first, $second);
    }
}