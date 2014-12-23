<?php
use Vda\Datasource\Relational\Driver\Mysql\MysqlConnection;
use Vda\Util\Type;

class MysqlConnectionTestClass extends PHPUnit_Framework_TestCase
{
    public function testMysql()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test');
        $this->assertTrue($conn->isConnected());

        $res = $conn->query('SHOW VARIABLES');
        $this->assertGreaterThan(0, $res->numRows());
    }

    public function testMysqlDialectOperabilityAfterReconnect()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test');
        $dialect = $conn->getDialect();
        $this->assertEquals("'qwe\\'asd'", $dialect->quote('qwe\'asd', Type::STRING));

        $conn->disconnect();
        $conn->connect();

        $this->assertEquals("'qwe\\'asd'", $dialect->quote('qwe\'asd', Type::STRING));
    }
}
