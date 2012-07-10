<?php

/**
 * Update to change format of file wa-config/auth.php
 *
 * return array(
 *     $domain => array(
 *         'adapters' => array(
 *          ...
 *         )
 *     )
 * )
 */

/**
 * @var waAppConfig $this
 */
$path = $this->getPath('config', 'auth');
if (file_exists($path)) {
    $config = include($path);
    $save = false;
    foreach ($config as $domain => $adapters) {
        if (!isset($adapters['adapters'])) {
            $config[$domain] = array(
                'adapters' => $adapters
            );
            $save = true;
        }
    }
    if ($save) {
        waUtils::varExportToFile($config, $path);
    }
}
