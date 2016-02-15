<?php

use Vda\Datasource\KeyValue\PeclMemcacheStorage;

class PeclMemcacheStorageTestClass extends AbstractStorageTestClass
{
    const HOST = '127.0.0.1';
    const PORT = 11211;

    public static function setUpBeforeClass()
    {
        try {
            self::$storage = new PeclMemcacheStorage(self::HOST . ':' . self::PORT);
        } catch (Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }
}
