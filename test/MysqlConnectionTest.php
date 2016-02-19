<?php
use Vda\Datasource\Relational\Driver\Mysql\MysqlConnection;
use Vda\Util\Type;

class MysqlConnectionTestClass extends AbstractMysqlTestClass
{
    private static $errorLevel = 0;

    public static function setUpBeforeClass()
    {
        self::$errorLevel = error_reporting(error_reporting() & ~E_DEPRECATED);

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        error_reporting(self::$errorLevel);
    }

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

    public function testMysqlPersistableChange()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test?persistent=1');

        ob_start();
        var_dump($conn);
        $this->assertRegExp('~resource\(\d+\) of type \(mysql link persistent\)~', ob_get_clean());

        $conn->disconnect();
        $conn->setPersistable(false);
        $conn->connect();

        ob_start();
        var_dump($conn);
        $this->assertRegExp('~resource\(\d+\) of type \(mysql link\)~', ob_get_clean());
    }

    public function testTransaction()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test');
        $this->assertFalse($conn->isTransactionStarted());
        $res = $conn->transaction(function() use ($conn) {
            $this->assertTrue($conn->isTransactionStarted());
            return 25;
        });
        $this->assertEquals(25, $res);
        $this->assertFalse($conn->isTransactionStarted());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage qwe
     */
    public function testTransactionException()
    {
        $conn = new MysqlConnection('mysql://root@localhost/test');
        $conn->transaction(function() {
            throw new Exception('qwe');
        });
    }
}
