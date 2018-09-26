<?php
/**
 * Webasyst Memcache cache adapter.
 * 
 * Ceche config:
 * array(
 *   'type' => 'memcache',
 *   'namespace' => 'domain',
 *   'servers' => array(
 *     array(
 *       'host' => '127.0.0.1',
 *       'port' => 11211,
 *       'persistent' => true,
 *       'weight' => 1,
 *     ),
 *   ),
 * ),
 */
class waMemcacheCacheAdapter extends waCacheAdapter
{
    /**
     * @var Memcache
     */
    protected $memcache;

    /**
     * @return void
     */
    protected function init()
    {
        if (!$this->memcache) {
            $this->memcache = new Memcache;
            if (empty($this->options['servers'])) {
                $this->memcache->addServer(
                    '127.0.0.1', 
                    ini_get('memcache.default_port')
                );
            } else {
                foreach ($this->options['servers'] as $s) {
                    $this->memcache->addServer(
                        $s['host'], 
                        ifset($s['port'], ini_get('memcache.default_port')),
                        ifset($s['persistent'], true),
                        ifset($s['weight'], 1)
                    );
                }
            }
            ifset(
                $this->options['namespace'], 
                wa()->getConfig()->getDomain()
            );
        }
        parent::init();
    }

    /**
     * @param string $key
     * @param string $app_id 
     * @param string $group
     * @return string
     */
    public function key($key, $app_id, $group = null)
    {
        $key = parent::key($key, $app_id. $group);
        return $this->options['namespace'] . '/' . $key;
    }

    /**
     * @param string $key
     * @param string $group
     * @return mixed
     */
    public function get($key, $group = null)
    {
        $r = $this->memcache->get($key);
        if ($r === false) {
            return null;
        }
        return $r;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     * @param string $group
     * @return bool
     */
    public function set($key, $value, $expiration = 0, $group = null)
    {
        if ($group) {
            $keys = $this->get($group);
            if (!$keys) {
                $keys = array();
            }
            $keys[] = $key;
            $this->set($group, $keys);
        }
        return $this->memcache->set($key, $value, 1, $expiration);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return $this->memcache->delete($key);
    }

    /**
     * @param string $group
     * @return bool
     */
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

    /**
     * @return bool
     */
    public function deleteAll()
    {
        return $this->memcache->flush();
    }
}
