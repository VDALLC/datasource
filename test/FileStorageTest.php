<?php

use Vda\Datasource\KeyValue\FileStorage;

class FileStorageTestClass extends AbstractStorageTestClass
{
    const CACHE_FILE = '/tmp/fileStorageTest.tmp';

    public static function setUpBeforeClass()
    {
        try {
            self::$storage = new FileStorage(self::CACHE_FILE);
        } catch (Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }
}
