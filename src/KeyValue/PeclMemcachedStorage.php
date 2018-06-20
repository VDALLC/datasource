<?php
namespace Vda\Datasource\KeyValue;

use Vda\Datasource\DatasourceException;

class PeclMemcachedStorage implements IStorage
{
    /**
     * @var \Memcached
     */
    private $backend;

    public function __construct($addr, $persistent_id = null)
    {
        if (!class_exists('\Memcached')) {
            throw new DatasourceException(
                'The \\Memcached class not found. Is pecl-memcached installed?'
            );
        }

        if ($persistent_id) {
            $this->backend = new \Memcached($persistent_id);
        } else {
            $this->backend = new \Memcached();
        }

        if (preg_match('!^(.*)(?:\:(\d+)?)$!', $addr, $parts)) {
            $addr = $parts[1];
            $port = empty($parts[2]) ? 0 : $parts[2];
        } else {
            $port = 0;
        }

        if (!count($this->backend->getServerList())) {
            if (!$this->backend->addServer($addr, $port)) {
                throw new DatasourceException("Unable to connect to {$addr}");
            }
        }
    }

    public function get($key)
    {
        return $this->backend->get('val:' . $key);
    }

    public function getExpirationTime($key)
    {
        return $this->backend->get('exp:' . $key);
    }

    public function add($key, $value, $ttl = 0)
    {
        if ($ttl > 0) {
            $ttl += time();
        }

        if ($this->backend->add('val:' . $key, $value, $ttl)) {
            $this->backend->set('exp:' . $key, $ttl, $ttl);

            return true;
        }

        return false;
    }

    public function set($key, $value, $ttl = 0)
    {
        if ($ttl > 0) {
            $ttl += time();
        }

        if ($this->backend->set('val:' . $key, $value, $ttl)) {
            $this->backend->set('exp:' . $key, $ttl, $ttl);

            return true;
        }

        return false;
    }

    public function delete($key)
    {
        $this->backend->delete('exp:' . $key);

        return $this->backend->delete('val:' . $key);
    }

    public function inc($key, $delta = 1)
    {
        return $this->backend->increment('val:' . $key, $delta);
    }

    public function dec($key, $delta = 1)
    {
        return $this->backend->decrement('val:' . $key, $delta);
    }
}
