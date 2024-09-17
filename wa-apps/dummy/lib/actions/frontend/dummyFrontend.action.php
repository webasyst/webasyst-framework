<?php

/**
 * Default frontend action
 * Read more about actions and controllers at
 * https://www.webasyst.com/framework/docs/dev/controllers/
 *
 * Read more about request routing in frontend at
 * https://www.webasyst.com/framework/docs/dev/routing/
 *
 * Экшен фронтенда по умолчанию
 * Подробнее о экшенах и контроллерах:
 * https://www.webasyst.com/ru/framework/docs/dev/controllers/
 *
 * Подробнее о маршрутизации во фронтенде:
 * https://www.webasyst.com/ru/framework/docs/dev/routing/
 */
class dummyFrontendAction extends waViewAction
{
    public function execute()
    {
        $message = 'Hello world!';
        // Passing data to template (actions/frontend/Frontend.html)
        // Передаем данные в шаблон (actions/frontend/Frontend.html)
        $this->view->assign('message', $message);

        // Подгатавливаем данные для передачи в событии
        $params = array(
            'request' => waRequest::request(),
            'message' => $message,
        );

        // Объявляем список ключей, ключей, которые ожидаются в шаблоне
        $expected_keys = array(
            'before_message',
            'after_message',
        );
        /**
         *          * @return array[string][string]string $return[%plugin_id%]['aux_li'] Single menu items
         * @return array[string][string]string $return[%plugin_id%]['core_li'] Single menu items
         */
        // Описываем событие, передаваемые параметры и ожидаемый результат

        /**
         * @event frontend_view Отображение основной пользовательской страницы
         * Используется для вывода дополнительной информации на основной странице
         * @param string [string] $params['message'] Message, passed into template
         * @return array[string][string]string $return[%plugin_id%] ['before_message'] HTML строка, отображаемая перед сообщением `message`.
         * @return array[string][string]string $return[%plugin_id%]['after_message'] HTML строка, отображаемая после сообщения `message`.
         * @example Place some code example here
         */
        $data = wa()->event('frontend_view', $params, $expected_keys);

        // Передаем данные по событию в шаблон от плагинов приложения
        $this->view->assign('event', $data);
    }
}
