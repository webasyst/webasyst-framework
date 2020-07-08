<?php
$path = dirname(__FILE__).'/wa-installer/install.php';
if (file_exists($path)) {
    require_once($path);
} else {
    //404
}
