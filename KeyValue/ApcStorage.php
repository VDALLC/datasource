<?php
namespace Vda\Datasource\KeyValue;

use Vda\Datasource\DatasourceException;

class ApcStorage implements IStorage
{
    public function __construct()
    {
        if (!function_exists('apc_inc')) {
            throw new DatasourceException(
                'The apc_inc function not found. Is >=pecl-apc-3.1.1 installed?'
            );
        }
    }

    public function get($key)
    {
        return apc_fetch('val:' . $key);
    }

    public function getExpirationTime($key)
    {
        return apc_fetch('exp:' . $key);
    }

    public function add($key, $value, $ttl = 0)
    {
        if (apc_add('val:' . $key, $value, $ttl)) {
            apc_store('exp:' . $key, $ttl + time(), $ttl);

            return true;
        }

        return false;
    }

    public function set($key, $value, $ttl = 0)
    {
        if (apc_store('val:' . $key, $value, $ttl)) {
            apc_store('exp:' . $key, $ttl + time(), $ttl);

            return true;
        }

        return false;
    }

    public function delete($key)
    {
        apc_delete('exp:' . $key);

        return apc_delete('val:' . $key);
    }

    public function inc($key, $delta = 1)
    {
        return apc_inc('val:' . $key, $delta);
    }

    public function dec($key, $delta = 1)
    {
        return apc_dec('val:' . $key, $delta);
    }
}
