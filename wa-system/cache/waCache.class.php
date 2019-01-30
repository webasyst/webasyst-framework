<?php
/**
 * Interface for a key/value storage such as Memcached.
 * See waSystem->getCache(), waSystemConfig->getCache()
 */
class waCache
{
    /**
     * @var waCacheAdapter
     */
    protected $adapter;
    protected $app_id;

    /**
     * @param waCacheAdapter $adapter
     * @param string $app_id
     */
    public function __construct(waCacheAdapter $adapter, $app_id)
    {
        $this->adapter = $adapter;
        $this->app_id = $app_id;
    }

    /**
     * @param string $key
     * @param string|bool $group
     * @return string
     */
    protected function key($key, $group = null)
    {
        return $this->adapter->key($key, $this->app_id, $group);
    }

    /**
     * @param string $key
     * @param string $group
     * @return mixed
     */
    public function get($key, $group = null)
    {
        return $this->adapter->get($this->key($key, $group));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string|bool $group
     * @param int $expiration
     * @return mixed
     */
    public function set($key, $value, $expiration = null, $group = null)
    {
        return $this->adapter->set($this->key($key, $group), $value, $expiration, $this->key($group, true));
    }

    /**
     * @param string $key
     * @param string
     * @return mixed
     */
    public function delete($key, $group = null)
    {
        return $this->adapter->delete($this->key($key, $group));
    }

    /**
     * @param string $group
     * @return mixed
     */
    public function deleteGroup($group)
    {
        return $this->adapter->deleteGroup($this->key($group, true));
    }

    /**
     * @return mixed
     */
    public function deleteAll()
    {
        return $this->adapter->deleteAll();
    }
}