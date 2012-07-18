<?php

/**
 * Здесь задаются правила внутренней маршрутизации для фронтенда URL => MODULE/[ACTION]
 * @see http://www.webasyst.com/ru/framework/docs/dev/routing/
 */
return array(
    // форма логина
    'login/' => 'login',
    // восстановление пароля
    'forgotpassword/' => 'forgotpassword',
    // форма регистрации
    'signup/' => 'signup',
    // подтверждение email нового пользователя
    'confirm/' => 'frontend/confirm',
    // фронтенд приложения Гостевая книга 2
    '(<page:\d+>/)?' => 'frontend'
);