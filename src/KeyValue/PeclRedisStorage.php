<?php
namespace Vda\Datasource\KeyValue;

use Vda\Datasource\DatasourceException;

class PeclRedisStorage implements IStorage
{
    /**
     * @var \Redis
     */
    private $backend;

    public function __construct($addr, $persistent = true)
    {
        if (!class_exists('\Redis')) {
            throw new DatasourceException(
                'The \Redis class not found. Is pecl-redis installed?'
            );
        }

        $this->backend = new \Redis();

        if (preg_match('!^(.*)(?:\:(\d+)?)$!', $addr, $parts)) {
            $addr = $parts[1];
            $port = empty($parts[2]) ? 0 : $parts[2];
        } else {
            $port = 0;
        }

        $connect = $persistent ? 'pconnect' : 'connect';

        if (!$this->backend->{$connect}($addr, $port)) {
            throw new DatasourceException("Unable to connect to {$addr}");
        }
    }

    public function get($key)
    {
        return $this->backend->get($key);
    }

    public function getExpirationTime($key)
    {
        if (!$this->backend->exists($key)) {
            return false;
        }

        return $this->backend->ttl($key) + time();
    }

    public function add($key, $value, $ttl = 0)
    {
        $result = $this->backend->setnx($key, $value);

        if ($result === true && $ttl > 0) {
            $this->backend->expire($key, $ttl);
        }

        return $result;
    }

    public function set($key, $value, $ttl = 0)
    {
        return $this->backend->set($key, $value, $ttl);
    }

    public function delete($key)
    {
        return (boolean) $this->backend->del($key);
    }

    public function inc($key, $delta = 1)
    {
        return $this->backend->incrBy($key, $delta) !== false;
    }

    public function dec($key, $delta = 1)
    {
        return $this->backend->decrBy($key, $delta) !== false;
    }
}
