<?php
use Vda\Datasource\Relational\Driver\Mysql\MysqlConnection;

abstract class AbstractMysqlTestClass extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        if (!function_exists('mysql_connect')) {
            self::markTestSkipped("Mysql extension isn't installed");
        }

        try {
            new MysqlConnection('mysql://root@localhost/test');
        } catch (Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }
}
