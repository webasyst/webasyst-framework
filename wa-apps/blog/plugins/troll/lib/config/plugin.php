<?php

return array(
	'name'			 => ('Troll'),//_wp('Troll')
	'description'	 => ("Mark selected users with a troll face"),//_wp("Mark selected users with a troll face")
	'vendor'		 => 'webasyst',
	'version'		 => '1.0.0',
    'img'=>'img/troll.png',
	'settings'=>function_exists('_wp')?array(
		'email' => array(
			'title' => _wp('Troll list'),
			'description' =>_wp("A list defining troll search criteria (each line of the textarea defines a criteria). If any of the criteria match commentator's email, name or site URL, a troll face is shown.<br /><br />Example:<br /><em>koe9s@gmail.com<br />unwanteddomain.com<br />Alxs29<br />@troll.com</em>"),
			'value' => '',
			'settings_html_function'=>'textarea',
		),
	):null,

	'handlers'		 => array(
		'backend_comments' => 'addControls',
		'backend_post' => 'addControls',
		'prepare_comments_backend'=>'prepareView',
		'prepare_comments_frontend'=>'prepareView',
		'frontend_action_post'=>'postView',
	),
);