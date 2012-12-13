<?php

/**
 * Here internal request routing rules for frontend are defined (URL => MODULE/[ACTION])
 * Здесь задаются правила внутренней маршрутизации для фронтенда URL => MODULE/[ACTION]
 * @see http://www.webasyst.com/framework/docs/dev/routing/
 */
return array(
    // login form
    // форма логина
    'login/' => 'login',
    // password recovery
    // восстановление пароля
    'forgotpassword/' => 'forgotpassword',
    // registration form
    // форма регистрации
    'signup/' => 'signup',
    // new user email address confirmation
    // подтверждение email нового пользователя
    'confirm/' => 'frontend/confirm',
    // frontend of the Guestbook 2 application
    // фронтенд приложения Гостевая книга 2
    '(<page:\d+>/)?' => 'frontend'
);