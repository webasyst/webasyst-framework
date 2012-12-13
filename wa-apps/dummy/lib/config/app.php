<?php

/* 
 * IMPORTANT NOTE!
 * This dummy application is a ready-to-use template for developing your own application.
 * To begin the development, copy folder dummy to the folder with your app's name
 * and change at all places dummy to the your app's APP_ID.
 * Failure to do so may result in accident loss of your code changes during update of your app via the Installer.
 * Read more at
 * http://www.webasyst.com/apps/dummy/
 * 
 * Also note that enabling the debug option is strongly advisable for application development.
 * To enable this option, ensure that the following code is added to file wa-config/config.php:
 *     'debug' => true
 *
 * ВНИМАНИЕ!
 * Это приложение "пустышка" - готовый каркас для разработки нового приложения.
 * Чтобы начинать разработку на основе этого каркаса скопируйте папку dummy в папку с названием вашего приложения,
 * замените везде dummy на APP_ID вашего приложения.
 * Иначе вы можете случайно потерять изменения при обновлении этого приложения через инсталлер.
 * Подробнее:
 * http://www.webasyst.com/ru/apps/dummy/
 * 
 * Также обратите внимание, что для разработки приложений крайне рекомендуется включить debug.
 * Для этого в файле wa-config/config.php должна быть строчка:
 *     'debug' => true
 */

/** 
 * Application's main configuration file
 * Read more about application configs at
 * http://www.webasyst.com/framework/docs/dev/config/#app.php
 *
 * Главный конфигурационный файл приложения
 * Подробнее о конфигах приложения:
 * http://www.webasyst.com/ru/framework/docs/dev/config/#app.php
 */
return array(
	// app name
	// название приложения
	'name' => 'Dummy',
	// relative path to app icon file
	// относительный путь до иконки приложения
	'img' => 'img/dummy.png',
    // developer name
    // разработчик приложения
	'vendor' => 'webasyst',
    // app version
    // номер версии
	'version' => '0.1',
	// extended access rights setup availability
	// флаг наличия расширенных прав
	'rights' => true,
    // frontend availability
    // флаг наличия фронтенда приложения
	'frontend' => true,
	// mobile version availability
	// флаг наличия мобильной версии приложения
	//'mobile' => true
);