<?php

require_once(dirname(__FILE__).'/../../wa-config/SystemConfig.class.php');

waSystem::getInstance(null, new SystemConfig('backend'));

// Init Webasyst application (system application)
waSystem::getInstance('webasyst');

// Execute api controller
try{
	$controller = new waAPIController();
	$controller->dispatch();
} catch (waAPIException $e) {
	print $e;
} catch (Exception $e) {
    $e = new waAPIException('server_error', $e->getMessage(), 500);
    print $e;
}