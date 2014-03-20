<?php

require_once __DIR__.'/../../wa-config/SystemConfig.class.php';

waSystem::getInstance(null, new SystemConfig('backend'));

// Init Webasyst application (system application)
waSystem::getInstance('webasyst');

// Execute API controller
try{
    $controller = new waAPIController;
    $controller->dispatch();
} catch (waAPIException $e) {
    echo $e;
} catch (Exception $e) {
    echo (new waAPIException('server_error', $e->getMessage(), 500));
}
