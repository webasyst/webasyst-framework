<?php

// Create cli.php if not included in distr already
$path = wa()->getConfig()->getRootPath().'/cli.php';
if(!file_exists($path)) {
    if($fp = fopen($path,'w')) {
        $content = <<<CLI
#!/usr/bin/php
<?php
require_once(dirname(__FILE__).'/wa-system/cli.php');

CLI;
        fwrite($fp,$content);
        fclose($fp);
    }
}

// Protect private dirs with .htaccess
$paths = array('log','cache','config','installer');
foreach ($paths as $path) {
    $path = waSystem::getInstance()->getConfig()->getPath($path);
    waFiles::protect($path);
}

// Insert data into tables
foreach(array('wa_country', 'wa_region') as $table) {
    if ( ( $sql = @file_get_contents(dirname(__FILE__).'/'.$table.'.sql'))) {
        try {
            $m = new waModel();
            $m->exec($sql);
        } catch (Exception $e) {
            waLog::log('Unable to populate '.$table.': '.$e->getMessage()."\n".$e->getTraceAsString());
        }
    }
}

