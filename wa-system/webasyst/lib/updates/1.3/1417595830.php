<?php

$model = new waAppSettingsModel();

if (!$model->get('webasyst', 'sender')) {
    $email = $model->get('webasyst', 'email');
    if ($email) {
        $model->set('webasyst', 'sender', $email);
    }
}