<?php 

class waMemcachedCache implements waiCache
{
    /**
    * Key
    *
    * @var string
    */
    protected $key;
    
    /**
     * Expire time in sec
     *
     * @var int
     */
    protected $ttl;
    protected $value = null;
    
    /**
     * Memcached instance
     * 
     * @var Memcached
     */
    protected static $memcached;
    
    public function __construct($key, $ttl = 0, $app_id = null)
    {
        if (!$app_id) {
            $app_id = wa()->getApp();
        }
        $this->key = $app_id.'.'.trim($key, '/');
        $this->ttl = $ttl;
        if (!self::$memcached) {
            self::$memcached = new Memcached('wa');
            $config = waSystem::getInstance()->getConfig()->getConfigFile('memcached');
            if (!$config) {
                throw new waException('Memcache config not found');
            }
            self::$memcached->addServer($config['host'], $config['port']);
        }
    }
    
    public function get()
    {
        if ($this->value !== null) {
            return $this->value;
        }
        $this->value = self::$memcached->get($this->key);
        return $this->value;
    }
    
    public function set($value)
    {
        $this->value = $value;
        return self::$memcached->set($this->key, $value, $this->ttl);
    }
    
    public function delete()
    {
        return self::$memcached->delete($this->key);
    }
    
    public function isCached()
    {
        return $this->get() === null ? false : true;
    }
}