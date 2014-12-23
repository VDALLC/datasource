<?php
use Vda\Datasource\Relational\Driver\Mysqli\MysqlConnection as Mysqli;
use Vda\Datasource\Relational\Driver\Mysql\MysqlConnection as Mysql;

class ConnectionTestClass extends PHPUnit_Framework_TestCase
{
    public function testMysqli()
    {
        $conn = new Mysqli('mysql://root@localhost/test');
        $this->assertTrue($conn->isConnected());

        $res = $conn->query('SHOW VARIABLES');
        $this->assertGreaterThan(0, $res->numRows());
    }

    public function testMysql()
    {
        $conn = new Mysql('mysql://root@localhost/test');
        $this->assertTrue($conn->isConnected());

        $res = $conn->query('SHOW VARIABLES');
        $this->assertGreaterThan(0, $res->numRows());
    }
}
