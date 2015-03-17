<?php

class waFileCacheAdapter extends waCacheAdapter
{
    /**
     * @var Memcached
     */
    protected static $memcached;

    protected function init()
    {
        if (!isset($this->options['path'])) {
            $this->options['path'] = waConfig::get('wa_path_cache').'/apps';
        }
    }

    public function key($key, $app_id, $group = null)
    {
        $key = trim($key, '/');
        if (!$group || $group === true) {
            return $app_id.'/cache/'. $key;
        } else {
            return $app_id.'/cache/'.$group.'/'.$key;
        }
    }


    public function get($key)
    {
        $file = $this->options['path'].'/'.$key.'.php';
        if (file_exists($file) && is_writable($file)) {
            $info = unserialize(file_get_contents($file));
            if (!empty($info['ttl']) && time() - $info['time'] >= $info['ttl']) {
                return null;
            } else {
                return $info['value'];
            }
        }
        return null;
    }

    public function set($key, $value, $expiration = null, $group = null)
    {
        $file = waFiles::create($this->options['path'].'/'.$key.'.php');
        $data = serialize(array('time' => time(), 'ttl' => $expiration, 'value' => $value));
        if (!file_exists($file) || is_writable($file)) {
            $r = @file_put_contents($file, $data, LOCK_EX);
            if ($r) {
                @chmod($file, 0664);
            }
            return $r;
        }
    }

    public function delete($key)
    {
        return waFiles::delete($this->options['path'].'/'.$key);
    }

    public function deleteGroup($group)
    {
        return waFiles::delete($this->options['path'].'/'.$group);
    }

    public function deleteAll()
    {
        return waFiles::delete($this->options['path']);
    }
}