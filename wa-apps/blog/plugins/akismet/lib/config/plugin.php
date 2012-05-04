<?php

return array(
	'name' => 'Akismet',//_wp('Akismet');
	'description' => 'Anti-spam comment fitering powered by Akismet.com',//_wp('Akismet comment filtering');
	'vendor'=>'webasyst',
	'version'=>'1.0.0',
	'img'=>'img/akismet.png',
	'settings' => function_exists('_wp')?array(
		'api_key' => array(
			'title'			 => _wp('Akismet API Key'),
			'description'	 => sprintf(_wp('Get an API key for your domain at <a target="_blank" href="%s">Akismet website</a>'),'https://akismet.com/signup/'),
			'value'			 => '',
			'settings_html_function'=>'input',
		),
		'send_spam' => array(
			'title'=>_wp('Report spam'),
			'label'=>_wp('Send comments marked as spam to Akismet server'),
			'settings_html_function'=>'checkbox',
		),
	):null,
	'rights' => false,
	'handlers' => array(
			'comment_presave_frontend' => 'commentPresaveFrontend',
			'backend_post' => 'addControls',
			'backend_comments' => 'addControls',
	),
);