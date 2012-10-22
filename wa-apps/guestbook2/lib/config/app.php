<?php

/**
 * Описание приложения Гостевая книга 2
 * @see http://www.webasyst.com/ru/framework/docs/dev/config/
 */
return array(
    // название приложения
    'name' => 'Guestbook 2',
    // относительный путь до иконки приложения
    'img' => 'img/guestbook2.png',
    'icon'=>array(
        16=>'img/guestbook16.png',
        24=>'img/guestbook24.png',
        48=>'img/guestbook2.png',
    ),
    // есть детальная настройка прав приложения (описана в конфиге guestbook2RightConfig.class.php)
    'rights' => true,
    // есть фронтенд
    'frontend' => true,
    // темы дизайна
    'themes' => true,
    // поддерживает авторизацию/регистрацию пользователей во фронтенде
    'auth' => true,
    // версия приложения
    'version'=>'1.1',
    // последнее важное обновление
    'critical'=>'1.1',
    // разработчик
    'vendor' => 'webasyst',
);