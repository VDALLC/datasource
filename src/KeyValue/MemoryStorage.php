<?php
namespace Vda\Datasource\KeyValue;

class MemoryStorage implements IStorage
{
    private $data = [];
    private $expire = [];

    public function get($key)
    {
        return $this->isExistAndNotExpired($key) ? $this->data[$key] : false;
    }

    public function getExpirationTime($key)
    {
        return $this->isExistAndNotExpired($key) ? $this->expire[$key] : false;
    }

    public function add($key, $value, $ttl = 0)
    {
        return $this->isExistAndNotExpired($key) ? false : $this->set($key, $value, $ttl);
    }

    public function set($key, $value, $ttl = 0)
    {
        $this->data[$key] = $value;
        $this->expire[$key] = empty($ttl) ? 0 : \time() + $ttl;

        return true;
    }

    public function delete($key)
    {
        $result = $this->isExistAndNotExpired($key);

        if ($result) {
            $this->remove($key);
        }

        return $result;
    }

    public function inc($key, $delta = 1)
    {
        $result = $this->isExistAndNotExpired($key);

        if ($result) {
            $this->data[$key] += $delta;
        }

        return $result;
    }

    public function dec($key, $delta = 1)
    {
        return $this->inc($key, -$delta);
    }

    private function isExistAndNotExpired($key)
    {
        if (!\array_key_exists($key, $this->data)) {
            return false;
        }

        if (!empty($this->expire[$key]) && $this->expire[$key] <= \time()) {
            $this->remove($key);
            return false;
        }

        return true;
    }

    private function remove($key)
    {
        unset($this->data[$key], $this->expire[$key]);
    }
}
