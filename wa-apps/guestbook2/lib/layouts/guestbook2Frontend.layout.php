<?php

/**
 * Лайаут (макет) фронтенда
 * Используется на всех страницах фронтенда
 * @see http://www.webasyst.com/ru/framework/docs/dev/layouts/
 */
class guestbook2FrontendLayout extends waLayout
{
    public function execute()
    {
        // задаём шаблон темы (themes/default/index.html
        $this->setThemeTemplate('index.html');
    }
}