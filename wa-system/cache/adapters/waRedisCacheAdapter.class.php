<?php

/**
 * Class waRedisCacheAdapter
 *
 * @author Vadym Korovin <danforth@live.ru>
 * @version 0.0.1
 */
class waRedisCacheAdapter extends waCacheAdapter
{
    /**
     * @var Redis
     */
    protected static $redis;
    protected static $db_index;

    protected function init()
    {
        if (!self::$redis) {
            self::$redis = new Redis();

            // currently supports only one instance
            // TODO: add pool of servers
            $this->options = $this->options['servers'][0];

            if ($this->options['port'] == 0) { // socket mode
                if ($this->options['persistent']) {
                    self::$redis->pconnect($this->options['host']);
                } else {
                    self::$redis->connect($this->options['host']);
                }
            } else {
                if ($this->options['persistent']) {
                    self::$redis->pconnect($this->options['host'], $this->options['port']);
                } else {
                    self::$redis->connect($this->options['host'], $this->options['port']);
                }
            }

            if ($this->options['password']) {
                self::$redis->auth($this->options['password']);
            }

            if ($this->options['db']) {
                self::$db_index = (int) $this->options['db'];
                self::$redis->select(self::$db_index);
            }
        }
    }

    private function join($group, $key)
    {
        if ($group !== '') {
            return $group . '/' . $key;
        }

        return $key;
    }

    public function get($key, $group = null)
    {
        $value = self::$redis->get($this->join($group, $key));
        if ($value === false) {
            return null;
        }
        return unserialize($value);
    }

    public function key($key, $app_id, $group = null)
    {
        return parent::key($key, $app_id, $group);
    }

    public function set($key, $value, $expiration = null, $group = null)
    {
        self::$redis->set($this->join($group, $key), serialize($value), $expiration);
    }

    public function delete($key)
    {
        return self::$redis->delete($key);
    }

    public function deleteGroup($group)
    {
        return self::$redis->delete($group . ':*');
    }

    public function deleteAll()
    {
        return self::$redis->flushDB();
    }
}
