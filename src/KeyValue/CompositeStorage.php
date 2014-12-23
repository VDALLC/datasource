<?php
namespace Vda\Datasource\KeyValue;

use Vda\Datasource\DatasourceException;
use Vda\Datasource\UnsupportedOperationException;

class CompositeStorage implements IStorage
{
    /**
     * @var IStorage[]
     */
    private $backends;

    public function __construct(array $backends)
    {
        foreach ($backends as $backend) {
            if (!($backend instanceof IStorage)) {
                throw new DatasourceException('All backends must implement IStorage interface');
            }
        }

        $this->backends = $backends;
    }

    public function get($key)
    {
        return $this->fetch($key, false);
    }

    public function getExpirationTime($key)
    {
        return $this->fetch($key, true);
    }

    public function add($key, $value, $ttl = 0)
    {
        $result = true;

        foreach ($this->backends as $b) {
            $result = $b->add($key, $value, $ttl) && $result;
        }

        return $result;
    }

    public function set($key, $value, $ttl = 0)
    {
        $result = true;

        foreach ($this->backends as $b) {
            $result = $b->set($key, $value, $ttl) && $result;
        }

        return $result;
    }

    public function delete($key)
    {
        $result = false;

        foreach ($this->backends as $b) {
            $result = $b->delete($key) || $result;
        }

        return $result;
    }

    public function inc($key, $delta = 1)
    {
        throw new UnsupportedOperationException(
            "Increment operation is undefined for composite storage"
        );
    }

    public function dec($key, $delta = 1)
    {
        throw new UnsupportedOperationException(
            "Decrement operation is undefined for composite storage"
        );
    }

    private function fetch($key, $needExpireTime)
    {
        $tried = array();

        foreach ($this->backends as $b) {
            $val = $b->get($key);

            if ($val !== false) {
                $exp = $b->getExpirationTime($key);
                $this->popupKey($key, $val, $exp, $tried);

                return $needExpireTime ? $exp : $val;
            }

            $tried[] = $b;
        }

        return false;
    }

    /**
     * @param $key
     * @param $val
     * @param $exp
     * @param IStorage[] $tried
     */
    private function popupKey($key, $val, $exp, $tried)
    {
        if ($exp > 0) {
            $exp -= time();
        }

        foreach ($tried as $t) {
            $t->set($key, $val, $exp);
        }
    }
}
