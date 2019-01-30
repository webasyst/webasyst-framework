<?php
/**
 * Script to generate contact photo thumbnails.
 */

// /wa-data/public/contacts/photos/09/00/9/1547652850.144x144@2x.jpg
$root_url = '../../../../../../../';
$path = realpath(dirname(__FILE__)."/../../../")."/wa-config/SystemConfig.class.php";
if (!file_exists($path)) {
    header("Location: {$root_url}wa-content/img/userpic96.jpg");
    exit;
}

require_once($path);
$config = new SystemConfig();
waSystem::getInstance(null, $config);

$file = wa()->getConfig()->getRequestUrl(true);
if (substr($file, 0, 10) == "thumb.php/") {
    // /wa-data/public/contacts/photos/thumb.php/09/00/9/1547652850.144x144@2x.jpg
    $file = substr($file, 10);
    $root_url .= '../';
}

$file = explode("/", $file);

if (count($file) != 4) {
    header("Location: {$root_url}wa-content/img/userpic96.jpg");
    exit;
}

$request_file = $file[3];
$contact_id = (int)$file[2];
$file = explode(".", $file[3]);

if (!$contact_id || count($file) != 3) {
    header("Location: {$root_url}wa-content/img/userpic96.jpg");
    exit;
}

$is_2x = '';
if (substr($file[1], -3) == '@2x') {
    $is_2x = '@2x';
    $file[1] = substr($file[1], 0, -3);
    $size = explode("x", $file[1]);
    foreach ($size as &$s) {
        $s *= 2;
    }
    unset($s);
} else {
    $size = explode("x", $file[1]);
}


if (count($size) != 2 || !$size[0] || !$size[1]) {
    header("Location: {$root_url}wa-content/img/userpic96{$is_2x}.jpg");
    exit;
}

$file = $file[0].".jpg";
$path = wa()->getDataPath(waContact::getPhotoDir($contact_id), true, 'contacts', false);
$filepath = "{$path}{$file}";

if (!file_exists($filepath)) {
    header("Location: {$root_url}wa-content/img/userpic96{$is_2x}.jpg");
    exit;
}

if ($size[0] == $size[1]) {
    waImage::factory($filepath)->resize($size[0], $size[1])->save($path.$request_file);
} else {
    waImage::factory($filepath)->resize($size[0], $size[1], waImage::INVERSE)->crop($size[0], $size[1])->save($path.$request_file);
}
clearstatcache();
waFiles::readFile($path.$request_file);
