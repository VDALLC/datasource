<?php
namespace Vda\Datasource\KeyValue;

interface IStorage
{
    public function get($key);
    public function getExpirationTime($key);
    public function add($key, $value, $ttl = 0);
    public function set($key, $value, $ttl = 0);
    public function delete($key);
    public function inc($key, $delta = 1);
    public function dec($key, $delta = 1);
}
