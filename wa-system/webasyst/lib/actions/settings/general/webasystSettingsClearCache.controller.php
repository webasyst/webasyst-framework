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

        $errors = array();
        if (!waSystemConfig::systemOption('cache_versioning')) {
            $paths = waFiles::listdir($path_cache);
            $root_path = wa()->getConfig()->getRootPath().DIRECTORY_SEPARATOR;
            foreach ($paths as $path) {
                $path = $path_cache.'/'.$path;
                if (is_dir($path)) {
                    try {
                        waFiles::delete($path);
                    } catch (Exception $ex) {
                        $errors[] = str_replace($root_path, '', $ex->getMessage());
                    }
                }
            }
        }

        if (!wa()->getConfig()->clearCache()) {
            if ($errors) {
                return $errors;
            } else {
                return array(_ws('Some files could not be deleted.'));
            }
        } else {
            return array(); // went fine the second time
        }

    }
}