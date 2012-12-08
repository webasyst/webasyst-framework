<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package installer
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
	'rights..|wa-installer|install.php|api.php|wa-log|wa-data/protected|wa-apps|wa-content|wa-cache'=>array(
		'description'=>'Check folder rights for install and update',
		'strict'=>true,
),

);

//EOF