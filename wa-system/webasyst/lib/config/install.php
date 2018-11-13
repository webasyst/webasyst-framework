<?php

$autoload = waAutoload::getInstance();
$autoload->add('webasystInstaller', '/wa-system/webasyst/lib/classes/webasystInstaller.class.php');

$_installer = new webasystInstaller();
$_installer->installAll();
