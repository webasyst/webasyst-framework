<?php

// move auth adapters to wa-system/auth/adapters

$path = $this->getPath('system').'/auth/';

$adapters = array('facebook', 'mailru', 'vkontakte', 'yandex', 'twitter', 'google');

foreach ($adapters as $adapter) {
    if (file_exists($path.$adapter)) {
        try {
            waFiles::delete($path.$adapter);
        } catch (Exception $e) {}
    }
}
