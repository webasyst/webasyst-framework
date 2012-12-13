<?php

/**
 * Frontend layout
 * Is used in all frontend pages
 *
 * Лайаут (макет) фронтенда
 * Используется на всех страницах фронтенда
 *
 * @see http://www.webasyst.com/framework/docs/dev/layouts/
 */
class guestbook2FrontendLayout extends waLayout
{
    public function execute()
    {
        // setting the theme template (themes/default/index.html)
        // задаём шаблон темы (themes/default/index.html)
        $this->setThemeTemplate('index.html');
    }
}