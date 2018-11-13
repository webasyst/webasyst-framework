<?php

class webasystSettingsClearCacheController extends webasystSettingsJsonController
{
    public function execute()
    {
        try {
            $errors = $this->flushCache();

            $this->response['message'] = _ws('Cache cleared');
            if ($errors) {
                $this->response['message'] .= "<br>"._ws('But with errors:')."<br>".implode("<br>", $errors);
            }
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }

    public function flushCache()
    {
        $path_cache = waConfig::get('wa_path_cache');
        waFiles::protect($path_cache);

        $caches = array();
        $paths = waFiles::listdir($path_cache);
        foreach ($paths as $path) {
            #skip long action & data path
            if ($path != 'temp') {
                $path = $path_cache.'/'.$path;
                if (is_dir($path)) {
                    $caches[] = $path;
                }
            }
        }

        $caches[] = $path_cache.'/temp';
        $root_path = wa()->getConfig()->getRootPath();
        $errors = array();
        foreach ($caches as $path) {
            try {
                waFiles::delete($path);
            } catch (Exception $ex) {
                $errors[] = str_replace($root_path.DIRECTORY_SEPARATOR, '', $ex->getMessage());
                waFiles::delete($path, true);
            }
        }

        $apps = wa()->getApps(true);
        foreach ($apps as $app_id => $app) {
            if ($cache = wa()->getCache('default', $app_id)) {
                try {
                    $cache->deleteAll();
                } catch (waException $ex) {
                    $errors[] = $ex->getMessage();
                }
            }
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        @clearstatcache();
        return $errors;
    }
}