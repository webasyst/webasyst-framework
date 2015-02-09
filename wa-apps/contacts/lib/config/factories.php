<?php 

return array(
	'auth' => array('waAuth', array('is_user' => false, 'login' => 'email')),
	'view' => array('waSmarty3View', array(
		'left_delimiter' => '{',
		'right_delimiter' => '}',
	)),
);

