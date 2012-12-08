<?php
/**
 *
 * @author Webasyst LLC
 * @package wa-installer
 *
 */
return array(
	'server'=>array(
),
	'php'=>array(
		'strict'=>true,
		'version'=>'>5.2',
),
	'php.curl'=>array(
		'description'=>'Get updates information from update servers',
		'strict'=>false,
),
	'phpini.allow_url_fopen'=>array(
		'description'=>'Get updates information from update servers',
		'strict'=>false,
		'value'=>1,
),
	'php.mbstring'=>array(
//	'description'=>'mbstring provides multibyte specific string functions that help you deal with multibyte encodings in PHP',
		'strict'=>true,
		'value'=>1,
),
	'rights..|wa-installer|install.php|index.php|api.php|wa-log|wa-data|wa-apps|wa-content|wa-cache'=>array(
		'description'=>'Check folder rights for install&amp;update',
		'strict'=>true,
),
	'server.mod_rewrite'=>array(
		'description'=>'Use friendly URLs',
		'strict'=>false,
		'config'=>'mod_rewrite',
),
	'md5.*.tar.gz|*.php'=>array(
		'description'=>'Check archives and files checksum',
		'strict'=>false,
		'silent'=>true,
		'allow_skip'=>true,
),
);

//EOF