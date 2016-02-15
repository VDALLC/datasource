<?php

use Vda\Datasource\KeyValue\IStorage;

abstract class AbstractStorageTestClass extends PHPUnit_Framework_TestCase
{
    /** @var IStorage */
    protected static $storage;

    public function testDelete()
    {
        $key = 'test_delete_key';
        $value = 'test_delete_value';

        self::$storage->delete($key);

        $result = self::$storage->delete($key);
        $this->assertFalse($result, 'Result of removing nonexistent key is not false');

        self::$storage->set($key, $value);
        $result = self::$storage->delete($key);
        $this->assertTrue($result, 'Result of removing existing key is not true');
    }

    public function testAdd()
    {
        $key = 'test_add_key';
        $value = 'test_add_value';

        self::$storage->delete($key);

        $result = self::$storage->add($key, $value);
        $this->assertTrue($result, 'The very first add operation failed');
        $this->assertEquals(
            $value,
            self::$storage->get($key),
            'Got unexpected value'
        );

        $result = self::$storage->add($key, $value . '_modified');
        $this->assertFalse($result, 'Subsequent add operation did not fail when it should');
        $this->assertEquals(
            $value,
            self::$storage->get($key),
            'Value has changed after subsequent add'
        );
    }

    public function testSet()
    {
        $key = 'test_set_key';
        $value = 'test_set_value';

        self::$storage->delete($key);

        $result = self::$storage->set($key, $value);
        $this->assertTrue($result, 'The very first set operation failed');
        $this->assertEquals(
            $value,
            self::$storage->get($key),
            'Got unexpected value'
        );

        $result = self::$storage->set($key, $value . '_modified');
        $this->assertTrue($result, 'Subsequent set operation failed');
        $this->assertEquals(
            $value . '_modified',
            self::$storage->get($key),
            'Value has not modified after subsequent set'
        );
    }

    public function testExpirationTime()
    {
        $key = 'test_expiration_time_key';
        $value = 'test_expiration_time_value';
        $timeout = 1;

        self::$storage->delete($key);

        $expirationTime = self::$storage->getExpirationTime($key);
        $this->assertFalse($expirationTime, 'Result of getting expiration of nonexistent key is not equal to false');

        self::$storage->set($key, $value, $timeout);

        $expirationTime = self::$storage->getExpirationTime($key);
        $this->assertNotEmpty($expirationTime);
        $this->assertGreaterThan(time(), $expirationTime, 'Expiration time is in past');

        sleep($timeout + 1);

        $receivedValue = self::$storage->get($key);
        $this->assertFalse($receivedValue, 'Key has not expired');
    }

    public function testIncDec()
    {
        $key = 'test_inc_dec_key';

        self::$storage->set($key, 42);

        self::$storage->inc($key);

        $this->assertEquals(
            43,
            self::$storage->get($key),
            'Unexpected inc result'
        );

        self::$storage->dec($key);
        $this->assertEquals(
            42,
            self::$storage->get($key),
            'Unexpected inc result'
        );
    }
}
