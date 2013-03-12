<?php

$path = $this->getPath('plugins');
$root = $this->getRootPath();
if (!file_exists($root.'/.svn') && !file_exists($root.'/.git')) {
    $plugin_types = array('shipping', 'payment');
    foreach ($plugin_types as $type) {
        $base_path = $path.'/'.$type.'/';
        foreach (waFiles::listdir($path.'/'.$type) as $plugin) {
            if (preg_match('/[A-Z]/', $plugin)) {
                try {
                    $plugin_path = $base_path.$plugin;
                    $file_name = $plugin_path.'/lib/'.$plugin.ucfirst($type).'.class.php';
                    $plugin = strtolower($plugin);

                    $dest_file_name = $plugin_path.'/lib/'.$plugin.ucfirst($type).'.class.php';
                    $dest_plugin_path = $base_path.$plugin;

                    waFiles::move($file_name, $dest_file_name);
                    waFiles::move($plugin_path, $dest_plugin_path);
                } catch (Exception $e) {
                    waLog::log($e->getMessage());
                }
            }
        }
        foreach (waFiles::listdir($path.'/'.$type) as $plugin) {
            if (preg_match('/[A-Z]/', $plugin)) {
                $plugin_path = $base_path.$plugin;
                try {
                    waFiles::delete($plugin_path);
                } catch (Exception $e) {
                    waLog::log($e->getMessage());
                }
            }
        }
    }
}
