<?php

use Vda\Datasource\KeyValue\MemoryStorage;

class MemoryStorageTestClass extends AbstractStorageTestClass
{
    public static function setUpBeforeClass()
    {
        try {
            self::$storage = new MemoryStorage();
        } catch (Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }
}
