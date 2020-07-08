<?php
return array(
    'ru_RU'
    => 'Русский',
    'Webasyst Installer'
    => 'Webasyst Installer',
    'Continue'
    => 'Продолжить',
    'System requirements'
    => 'Системные требования',
    'Files'
    => 'Файлы',
    'Settings'
    => 'Настройки',
    'Connect'
    => 'Подключиться',
    'Retry'
    => 'Повторить',
    'Webasyst Installer will deploy archive with Webasyst system files and apps in this folder.'
    => 'Установщик развернет в этой папке архив с ядром Вебасиста и системными приложениями.',
    'Install Webasyst'
    => 'Установить Webasyst',
    'Web Server'
    => 'Сервер',
    'Host'
    => 'Сервер',
    'Server fully satisfies Webasyst system requirements.'
    => 'Сервер удовлетворяет системным требованиям для установки Вебасиста.',
    'Server does not meet Webasyst system requirements. Installer can not proceed with the installation unless all requirements are satisfied.'
    => 'Сервер не удовлетворяет требованиям установки Вебасиста. Продолжение установки будет возможно только после того, как все системные требования будут удовлетворены.',
    'An error occurred while attempting to validate system requirements'
    => 'Произошла ошибка при проверке системных требований',
    'All files successfully extracted. Click "Continue" button below.'
    => 'Файлы успешно распакованы. Щелкните по кнопке «Продолжить».',

    'An error occurred during the installation'
    => 'Произошла ошибка при установке',
    'Failed to connect to the "%s" database. (%s)'
    => 'Ошибка подключения к базе данных «%s». (%s)',
    'Failed to connect to "%s" MySQL database server. (%s)'
    => 'Ошибка подключения к серверу "%s" базы данных MySQL. (%s)',

    'MySQL database'
    => 'База данных MySQL',
    'Enter connection credentials for the MySQL database which will be used by Webasyst to store system and application data.'
    => 'Введите настройки подключения к базе данных MySQL, в которой Вебасист создаст таблицы для хранения своих данных и данных приложений.',
    'User'
    => 'Пользователь',
    'Password'
    => 'Пароль',
    'Database Name'
    => 'Имя базы данных',
    'Webasyst cannot be installed into "%s" database because this database already contains Webasyst tables. Please specify connection credentials for another MySQL database.'
    => 'Установка в базу данных %s невозможна, так как в ней уже есть таблицы Вебасиста. Укажите настройки подключения к другой базе данных.',
    'The database already contains %d tables.'
    => 'База данных уже содержит %d таблиц.',

    'If you do not know what should be entered here, please contact your hosting provider technical support.'
    => 'Если вы не знаете, что вводить, обратитесь в службу поддержки вашего хостинг-провайдера.',
    'Installed!'
    => 'Установлено!',
    'Webasyst is installed and ready.'
    => 'Вебасист установлен и готов для использования.',
    'Remember this address. This is the address for logging into your Webasyst backend.'
    => 'Запомните этот адрес — это адрес входа в ваш Вебасист.',
    'Installation Guide'
    => 'Инструкции по установке',
    'install_quide_url'
    => 'https://www.webasyst.ru/developers/docs/installation/',
    'Extracting Webasyst arhcive...'
    => 'Распаковка архива Вебасист...',
    'Check available updates'
    => 'Проверить обновления',


    'error'
    => 'Произошла ошибка',
    'heartbeat'
    => ' ',
    'complete'
    => ' ',
    'prepare'
    => 'Подготовка',
    'copy'
    => 'Резервное копирование',
    'download'
    => 'Загрузка файлов',
    'extract'
    => 'Распаковка архива',
    'replace'
    => 'Обновление файлов',
    'cleanup'
    => 'Удаление временных файлов',
    'update'
    => 'Завершение',

    'Please install updates for the proper verification requirements'
    => 'Загрузите последнюю версию Вебасиста с сайта webasyst.ru для корректной проверки системных требований',
    'Unknown requirement case %s'
    => 'Неизвестное системное требование "%s"',

    'PHP version'
    => 'PHP',
    'PHP has version %s but should be %s %s'
    => 'Требуется версия PHP %2$s%3$s (текущая версия на сервере — %1$s)',

    'PHP extension %s'
    => 'Расширение PHP: %s',
    'extension %s has %s version but should be %s %s'
    => 'Требуется версия расширения %1$s %3$s%4$s, текущая версия %2$s',
    'extension %s has %s version but recommended is %s %s'
    => 'Рекомендуется версия расширения %1$s %3$s%4$s, текущая версия %2$s',
    'PHP extension %s is required'
    => 'Требуется расширение %s для PHP',
    'curl'
    => 'cURL',

    'PHP setting %s'
    => 'Настройка PHP: %s',
    'setting has value %s but should be %s'
    => 'Требуемое значение %2$s, текущее значение %1$s',
    'setting has value %s but recommended %s'
    => 'Рекомендуемое значение %2$s, текущее значение %1$s',

    'Version of %s'
    => '%s, версия',
    '%s has %s version but should be %s %s'
    => 'Требуется версия %1$s %3$s%4$s, текущая версия %2$s',
    '%s has %s version but recommended is %s %s'
    => 'Рекомендуется версия %1$s %3$s%4$s, текущая версия %2$s',
    '%s not installed'
    => 'Приложение %s не установлено',
    'installer'
    => 'Webasyst Installer',

    'Files access rights'
    => 'Права доступа к файлам и директориям на сервере',
    '%s should be writable'
    => 'Следующие файлы и папки должны быть доступны для записи: %s',
    '%s is writable'
    => 'Необходимые файлы и папки %s доступны для записи',

    'Files checksum'
    => 'Целостность файлов',

    'Server module %s'
    => 'Модуль сервера: %s',
    'server module loaded'
    => 'есть',
    'server module not loaded'
    => 'нет',
    'not Apache server'
    => 'Не удалось получить список установленных серверных модулей',
    'CGI PHP mode'
    => 'PHP запущен в режиме CGI',
    'Server software version'
    => 'Веб-сервер',


    'Use friendly URLs'
    => 'Есть возможность использовать ЧПУ',
    'Check archives and files checksum'
    => 'Проверка целостности архива с файлами установщика и приложениями Вебасиста',
    'Check folder rights for install&amp;update'
    => 'Необходимый уровень прав доступа для установки Вебасиста и обновлений',
    'Get updates information from update servers'
    => 'Требуется для получения информации об обновлениях с сервера Вебасиста',
    'Finalizing installation...'
    => 'Завершаем установку...',
);
