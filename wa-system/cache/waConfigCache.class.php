<?php
/**
 * Transparent memory cache for config files.
 *
 * To enable this caching strategy, set up default memory cache
 * (such as Memcache, XCache or Redis) via wa-config/cache.php, then set
 * 'config_cache_enable' => true in wa-config/config.php
 *
 * Note that file cache adapter makes no sense for this type of caching,
 * only in-memory cache should be used.
 */
class waConfigCache
{
    protected static $instance;

    /**
     * @var $cache_adapter waMemcachedCacheAdapter
     */
    protected static $cache_adapter = false;

    /**
     * @return waConfigCache singleton instance
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$cache_adapter = false;

            $framework_is_ready = function_exists('wa') && class_exists('waConfig') && waConfig::has('wa_path_root');

            // Do not use cache unless explicitly enabled
            if ($framework_is_ready && waSystemConfig::systemOption('config_cache_enable') === true) {
                try {
                    self::$cache_adapter = wa('wa-system')->getCache();
                } catch (Exception $e) {
                    $framework_is_ready = false;
                }
            }

            if (!$framework_is_ready) {
                // Framework is not ready, called too early.
                // Return an instance of self but do not save it to self::$instance
                // so that we attempt to do that again later.
                return new self();
            } else {
                self::$instance = new self();
            }
        }
        return self::$instance;
    }

    /**
     * Drop-in replacement for include() that transparently uses cache if available.
     * @param string $file  path to config file
     * @param bool $compare_filemtime false to return cache contents even if older than modification time of config file
     * @return mixed config file contents, as include() would return; usually PHP array
     */
    public function includeFile($file, $compare_filemtime = true)
    {
        $normalized_key = false;
        if (self::$cache_adapter) {
            $normalized_key = $this->normalizeKey($file);
        }

        if (self::$cache_adapter && $normalized_key) {
            $cache = self::$cache_adapter->get($normalized_key);
            if (!empty($cache['value']) && (!$compare_filemtime || $cache['timestamp'] >= filemtime($file))) {

                // config_cache_debug option allows to compare cached value to actual config file.
                // It will write to wa-log when cache returns different value compared to what is in file.
                // Note that it does nothing to fix the difference even when found.
                // This option should not be normally enabled in live environments.
                if (waSystemConfig::systemOption('config_cache_debug')) {
                    $value_from_file = include($file);
                    if ($value_from_file != $cache['value']) {
                        waLog::dump(
                            'Difference found between cached value and actual file config (compare_filemtime='.($compare_filemtime?'true':'false').').',
                            "cached data (cache key `{$normalized_key}`):",
                            $cache['value'],
                            "read from config file (file path `{$file}`):",
                            $value_from_file,
                            'config_cache_debug.log'
                        );
                    }
                }

                return $cache['value'];
            }
        }

        $new_cache = include($file);

        if (self::$cache_adapter && $normalized_key) {
            self::setFileContents($normalized_key, $new_cache);
        }

        return $new_cache;
    }

    /**
     * Remove cache contents for a single config file, or clear all cache.
     * @param string $file  path to config file; omit to clear the whole cache
     */
    public function clearCache($file = null)
    {
        if (self::$cache_adapter) {
            if ($file === null) {
                self::$cache_adapter->deleteAll();
            } else {
                $normalized_key = $this->normalizeKey($file);
                if ($normalized_key) {
                    self::$cache_adapter->delete($normalized_key);
                }
            }
        }
    }

    /**
     * Write config contents to cache without reading file from disk.
     * @param string $file  path to config file
     * @param mixed $data   config contents (usually PHP array)
     */
    public function setFileContents($file, $data)
    {
        if (self::$cache_adapter) {
            $normalized_key = $this->normalizeKey($file);
            if ($normalized_key) {
                self::$cache_adapter->set($normalized_key, array(
                    'value' => $data,
                    'timestamp' => time(),
                ));
            }
        }
    }

    /**
     * Normalize file path before using it as cache key
     * @param string $file  path to config file
     * @return bool|string valid cache key to use for given file, or false if file is not cacheable
     */
    protected function normalizeKey($file)
    {
        if (!function_exists('wa') || mb_strlen($file) <= 0) {
            return false;
        }
        $file = realpath($file);
        if (!$file) {
            return false;
        }
        try {
            $root_path = wa()->getConfig()->getRootPath().DIRECTORY_SEPARATOR;
        } catch (Exception $e) {
            // Framework is not ready, called too early
            return false;
        }
        if (mb_substr($file, 0, mb_strlen($root_path)) !== $root_path) {
            // do not cache anything outside of framework directory
            return false;
        }

        // remove root path from the begining of the key
        return mb_substr($file, mb_strlen($root_path));
    }
}
