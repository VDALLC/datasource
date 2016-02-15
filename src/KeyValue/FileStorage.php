<?php
namespace Vda\Datasource\KeyValue;

use Vda\Datasource\DatasourceException;

class FileStorage implements IStorage
{
    private $handle;
    private $filename;
    private $dataLoadTime;
    private $data;
    private $lockCount;

    public function __construct($filename)
    {
        $this->resetData();
        $this->dataLoadTime = 0;
        $this->lockCount = 0;
        $this->filename = $filename;
        $this->handle = fopen($filename, 'a+');
        @chmod($filename, 0666);

        if (!$this->handle) {
            throw new DatasourceException("Unable to open file for reading/writing: {$filename}");
        }
    }

    public function get($key)
    {
        return $this->getValueFromSection($key, 'values');
    }

    public function getExpirationTime($key)
    {
        return $this->getValueFromSection($key, 'expires');
    }

    public function add($key, $value, $ttl = 0)
    {
        return $this->updateParam($key, $value, $ttl, true);
    }

    public function set($key, $value, $ttl = 0)
    {
        return $this->updateParam($key, $value, $ttl);
    }

    public function delete($key)
    {
        if ($this->get($key) === false) {
            return false;
        }

        return $this->updateParam($key, null, -1);
    }

    public function inc($key, $delta = 1)
    {
        $this->lock(true);

        $this->loadData();

        if ($this->hasParam($key)) {
            $current = $this->get($key);
            $expire = $this->getExpirationTime($key);
            $expire = $expire == 0 ? 0 : $expire - time();
            $this->updateParam(
                $key,
                is_numeric($current) ? $current + $delta : $delta,
                $expire
            );
        }

        $this->unlock();
    }

    public function dec($key, $delta = 1)
    {
        return $this->inc($key, -$delta);
    }

    private function normalizeExpire($expire)
    {
        if ($expire > 0) {
            $expire += time();
        }

        return $expire;
    }

    private function updateParam($key, $value, $expire, $unique = false)
    {
        $this->lock(true);

        $this->loadData();

        if ($unique && $expire >= 0 && $this->hasParam($key)) {
            $this->unlock();
            return false;
        }

        $expire = $this->normalizeExpire($expire);

        $isUpdated = $this->isNewValue($key, $value, $expire);

        if ($isUpdated) {
            if ($expire < 0) {
                unset(
                    $this->data['values'][$key],
                    $this->data['expires'][$key]
                );
            } else {
                $this->data['values'][$key] = $value;
                $this->data['expires'][$key] = $expire;
            }

            $this->saveData();
        }

        $this->unlock();

        return true;
    }

    private function isNewValue($name, $value, $expire)
    {
        return !$this->hasParam($name) ||
               $this->data['values'][$name] != $value ||
               $this->data['expires'][$name] != $expire;
    }

    private function hasParam($name)
    {
        return array_key_exists($name, $this->data['values']) &&
                !$this->isParamExpired($name);
    }

    private function isParamExpired($name)
    {
        return !empty($this->data['expires'][$name]) &&
                time() >= $this->data['expires'][$name];
    }

    private function loadData()
    {
        clearstatcache();
        $dataModifyTime = intval(@filemtime($this->filename));
        if ($this->dataLoadTime != $dataModifyTime) {
            $this->dataLoadTime = $dataModifyTime;
            rewind($this->handle);
            $content = stream_get_contents($this->handle);

            if (empty($content)) {
                $this->resetData();
            } else {
                $this->data = unserialize($content);
            }
        }
    }

    private function resetData()
    {
        $this->data = array('values' => array(), 'expires' => array());
    }

    private function saveData()
    {
        foreach (array_keys($this->data['values']) as $name) {
            if ($this->isParamExpired($name)) {
                unset($this->data['values'][$name], $this->data['expires'][$name]);
            }
        }
        ftruncate($this->handle, 0);
        $data = serialize($this->data);
        if (fwrite($this->handle, $data) === false) {
            throw new DatasourceException("Unable to open file for reading/writing: {$this->filename}");
        }
    }

    private function getValueFromSection($key, $section)
    {
        $this->lock(false);
        $this->loadData();
        $this->unlock();

        if ($this->hasParam($key)) {
            return $this->data[$section][$key];
        }

        return false;
    }

    private function lock($exclusive)
    {
        if ($this->lockCount == 0) {
            flock($this->handle, $exclusive ? LOCK_EX : LOCK_SH);
        }

        $this->lockCount++;
    }

    private function unlock()
    {
        $this->lockCount--;

        if ($this->lockCount == 0) {
            flock($this->handle, LOCK_UN);
        }
    }
}
