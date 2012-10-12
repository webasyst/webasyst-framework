<?php
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
$paths = array('log','cache','config','installer');
foreach ($paths as $path) {
    $path = waSystem::getInstance()->getConfig()->getPath($path);
    waFiles::protect($path);
}
