<?php
$model = new waAppSettingsModel();
$model->set('blog', 'request_captcha', $model->get('blog', 'request_captcha', 0));

