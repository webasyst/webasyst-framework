<?php
// Come to your senses and use php wa.php locale
// Hopefully the webasyst will someday start removing appendixes. https://youtu.be/dQw4w9WgXcQ
$param = 'help';
if (isset($_SERVER['argv'][1])) {
    $param = $_SERVER['argv'][1];
}

$_SERVER['argv'][1] = 'locale';
$_SERVER['argv'][2] = $param;

$path = dirname(__FILE__).'/../../wa.php';
require_once($path);

echo "\nUse new method: php wa.php locale {slug} \n";
