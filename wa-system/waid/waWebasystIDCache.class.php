<?php

/**
 * waWebasystIDCache
 * If wa()->getCache() is available then use it otherwise use file cache
 */
class waWebasystIDCache implements waiCache
{
    private $ttl;
    private $key;

    /**
     * @param $key
     * @param int $ttl
     * @param null $app_id
     */
    public function __construct($key, $ttl = -1, $app_id = null)
    {
        $this->ttl = $ttl;
        $this->key = $key;
    }

    public function set($value)
    {
        $ttl = wa_is_int($this->ttl) ? $this->ttl : 0;

        if (!is_scalar($this->key)) {
            $key = var_export($this->key, true);
        } else {
            $key = $this->key;
        }

        // default waCache, that in webasyst cloud is memcache
        $cache = wa()->getCache();
        if ($cache) {
            if ($value === null || $ttl <= 0) {
                $cache->delete($key);
            } else {
                $cache->set($key, $value, strtotime(sprintf("add %d seconds", $ttl)));
            }
            return;
        }

        // fallback to file cache
        $cache = new waVarExportCache($key, $ttl);

        if ($value === null || $ttl <= 0) {
            $cache->delete();
            return;
        }

        $value = [
            'value' => $value,
            'time' => time(),
            'ttl' => $ttl
        ];

        $cache->set($value);
    }

    public function get()
    {
        if (!is_scalar($this->key)) {
            $key = var_export($this->key, true);
        } else {
            $key = $this->key;
        }

        $ttl = wa_is_int($this->ttl) ? $this->ttl : 0;

        $cache = wa()->getCache();
        if ($cache) {
            return $cache->get($key);
        }

        // fallback to file cache

        $cache = new waVarExportCache($key, $ttl);
        $value = $cache->get();
        if (!is_array($value) || !isset($value['value']) || !isset($value['ttl']) || !isset($value['time'])) {
            return null;
        }

        $elapsed = time() - $value['time'];
        if ($ttl <= 0 || $elapsed > $value['ttl']) {
            $cache->delete();
            return null;
        }

        return $value['value'];
    }

    public function delete()
    {
        $this->set(null);
    }

    public function isCached()
    {
        return $this->get() !== null;
    }
}
