<?php

$root_path = realpath(dirname(__FILE__)."/../../../");

$file_version = preg_replace("!^.*?/([^/]*)\.css.*!i", "$1", $_SERVER['REQUEST_URI']);

if (strpos($file_version, "mobile") === false) {
	$files = array(
		"wa.importexternal.css",	
		"wa.reset.css",
		"wa.base.css",
		"wa.app.css",
		"wa.layout.default.css",
		"wa.icons.css",
		"wa.jquery-ui.css",
	);
	$path = $file_version;
} else {
	$files = array(
		"wa.jquery-mobile.css",
		"wa.mobile.app.css",
	);
	$path = str_replace("-mobile", "", $file_version);
}

$css = "";
foreach ($files as $file) {
	$css .= file_get_contents($root_path."/ui2/wa-content/css/".$path."/".$file);
	$css .= "\r\n\r\n\r\n";
}
@file_put_contents($file_version.".css", $css);

header("Content-Type: text/css; charset=utf-8");
echo $css;