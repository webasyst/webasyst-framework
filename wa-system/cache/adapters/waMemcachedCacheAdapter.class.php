<?php

class waMemcachedCacheAdapter extends waCacheAdapter
{
    /**
     * @var Memcached
     */
    protected static $memcached;

    protected function init()
    {
        if (!self::$memcached) {
            self::$memcached = new Memcached('wa');
            if (empty($this->options['servers'])) {
                self::$memcached->addServer('127.0.0.1', 11211);
            } else {
                foreach ($this->options['servers'] as $s) {
                    self::$memcached->addServer($s['host'], isset($s['port']) ? $s['port'] : 11211,
                        isset($s['weight']) ? $s['weight'] : 0);
                }
            }
        }
    }

    public function key($key, $app_id, $group = null)
    {
        return (isset($this->options['namespace']) ? $this->options['namespace'].'/' : '').parent::key($key, $app_id. $group);
    }

    public function get($key, $group = null)
    {
        $r = self::$memcached->get($key);
        if ($r === false) {
            return null;
        }
        return $r;
    }

    public function set($key, $value, $expiration = null, $group = null)
    {
        if ($group) {
            $keys = $this->get($group);
            if (!$keys) {
                $keys = array();
            }
            $keys[] = $key;
            $this->set($group, $keys);
        }
        return self::$memcached->set($key, $value, $expiration);
    }

    public function delete($key)
    {
        return self::$memcached->delete($key);
    }

    public function deleteGroup($group)
    {
        $keys = $this->get($group);
        if ($keys) {
            foreach ($keys as $k) {
                $this->delete($k);
            }
        }
        return $this->delete($group);
    }

    public function deleteAll()
    {
        return self::$memcached->flush();
    }
}