<?php
return array(
	'app.installer'            => array(
		'strict'  => true,
		'version' => '1.5.8',
	),
	'php.openssl'              => array(
		'strict' => false,
	),
	'phpini.max_exection_time' => array(
		'name'        => 'Максимальное время исполнения PHP-скриптов',
		'description' => '',
		'strict'      => false,
		'value'       => '>60',
	),
	'php'                      => array(
		'strict'  => true,
		'version' => '>=5.3',
	),
);
