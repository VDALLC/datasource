<?php
namespace Vda\Datasource\KeyValue;

class KeyDecoratingStorage implements IStorage
{
    /**
     * @var IStorage
     */
    private $backend;
    private $decorator;

    public function __construct(callable $keyDecorator, IStorage $backend)
    {
        $this->backend = $backend;
        $this->decorator = $keyDecorator;
    }

    public function get($key)
    {
        return $this->backend->get($this->decorate($key));
    }

    public function getExpirationTime($key)
    {
        return $this->backend->getExpirationTime($this->decorate($key));
    }

    public function add($key, $value, $ttl = 0)
    {
        return $this->backend->add($this->decorate($key), $value, $ttl);
    }

    public function set($key, $value, $ttl = 0)
    {
        return $this->backend->set($this->decorate($key), $value, $ttl);
    }

    public function delete($key)
    {
        return $this->backend->delete($this->decorate($key));
    }

    public function inc($key, $delta = 1)
    {
        return $this->backend->inc($this->decorate($key), $delta);
    }

    public function dec($key, $delta = 1)
    {
        return $this->backend->dec($this->decorate($key), $delta);
    }

    private function decorate($str)
    {
        $f = $this->decorator;

        return $f($str);
    }
}
