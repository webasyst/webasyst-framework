<?php

/**
 * Description of the Guestbook 2 application
 * Описание приложения Гостевая книга 2
 * @see http://www.webasyst.com/framework/docs/dev/config/
 */
return array(
    // app name
    // название приложения
    'name' => 'Guestbook 2',
    // relative path to app icon file
    // относительный путь до иконки приложения
    'img' => 'img/guestbook2.png',
    'icon'=>array(
        16=>'img/guestbook16.png',
        24=>'img/guestbook24.png',
        48=>'img/guestbook2.png',
    ),
    // availability of extended access rights setup (as defined in config file guestbook2RightConfig.class.php)
    // есть детальная настройка прав приложения (описана в конфиге guestbook2RightConfig.class.php)
    'rights' => true,
    // frontend availability
    // есть фронтенд
    'frontend' => true,
    // availability of design themes
    // темы дизайна
    'themes' => true,
    // support for user authorization/registration in the frontend
    // поддерживает авторизацию/регистрацию пользователей во фронтенде
    'auth' => true,
    // app version
    // версия приложения
    'version'=>'1.1',
    // last critical update
    // последнее важное обновление
    'critical'=>'1.1',
    // developer name
    // разработчик
    'vendor' => 'webasyst',
);