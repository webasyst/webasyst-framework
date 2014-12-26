<?php
/**
 * @todo check allowed sizes
 * @todo use resize options (quiality and filters)
 * @todo use error handlers to display error while resize
 */


$path = realpath(dirname(__FILE__)."/../../../../../");
$config_path =$path."/wa-config/SystemConfig.class.php";
if (!file_exists($config_path)) {
    header("Location: ../../../../../../wa-apps/photos/img/image-not-found.png");
    exit;
}

require_once($config_path);
$config = new SystemConfig();
waSystem::getInstance(null, $config);

$request_file = wa()->getConfig()->getRequestUrl(true, true);

if (preg_match("@^thumb.php/(.+)@", $request_file, $matches)) {
    $request_file = $matches[1];
}

$public_path = $path.'/wa-data/public/photos/';
$protected_path = $path.'/wa-data/protected/photos/';

$main_thumb_file = false;
$file = false;
$size = false;

// app settings
/**
 * @var photosConfig $app_config
 */
$app_config = wa('photos')->getConfig();

$main_thumbnail_size = photosPhoto::getBigPhotoSize();

$enable_2x = false;

if (preg_match('#((?:\d{2}/){2}([0-9]+)(?:\.[0-9a-f]+)?)/\\2\.(\d+(?:x\d+)?)(@2x)?\.([a-z]{3,4})#i', $request_file, $matches)) {
    $file = $matches[1].'.'.$matches[5];
    $main_thumb_file = $matches[1].'/'.$matches[2].'.'.$main_thumbnail_size.'.'.$matches[5];
    $size = $matches[3];

    if ($file && !$app_config->getOption('thumbs_on_demand')) {
        $thumbnail_sizes = $app_config->getSizes();
        $thumbnail_sizes[] = '192x192'; // for album covers
        if (in_array($size, $thumbnail_sizes) === false) {
            $file = false;
        }
    }
    if ($matches[4] && $app_config->getOption('enable_2x')) {
        $enable_2x = true;
        $size = explode('x', $size);
        foreach ($size as &$s) {
            $s *= 2;
        }
        unset($s);
        $size = implode('x', $size);
    }
}
wa()->getStorage()->close();
if ($file && file_exists($protected_path.$file) && !file_exists($public_path.$request_file)) {

    $main_thumb_file_path = $public_path.$main_thumb_file;

    $target_dir_path = dirname($public_path.$request_file);
    if(!file_exists($target_dir_path)){
        waFiles::create($target_dir_path.'/');
    }
    $max_size = $app_config->getOption('max_size');
    $image = photosPhoto::generateThumb(array(
            'path' => $main_thumb_file_path,
            'size' => $main_thumbnail_size
        ),
        $protected_path.$file,
        $size,
        $app_config->getOption('sharpen'),
        $max_size ? ($enable_2x ? 2 * $max_size : $max_size) : false
    );
    if ($image) {
        $quality = $app_config->getSaveQuality($enable_2x);
        $image->save($public_path.$request_file, $quality);
        clearstatcache();
    }
}

if($file && file_exists($public_path.$request_file)){
    waFiles::readFile($public_path.$request_file);
} else{
    /*
    $url = wa()->getRootUrl();
    $url = substr($url, 0, -strlen('/wa-data/public/photo/'));
    header("Location: ".$url."wa-apps/photos/img/image-not-found.png");
    */
    header("HTTP/1.0 404 Not Found");
    exit;
}
