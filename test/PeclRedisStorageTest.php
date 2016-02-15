<?php

use Vda\Datasource\KeyValue\PeclRedisStorage;

class PeclRedisStorageTestClass extends AbstractStorageTestClass
{
    const HOST = '127.0.0.1';
    const PORT = 6379;

    public static function setUpBeforeClass()
    {
        try {
            self::$storage = new PeclRedisStorage(self::HOST . ':' . self::PORT);
        } catch (Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }
}
