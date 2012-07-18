<?php

/**
 * Фронтенд приложения Гостевая книга 2
 * Страница подтверждения email-адреса
 * @see http://www.webasyst.com/ru/framework/docs/dev/frontend-routing/
 * @see http://www.webasyst.com/ru/framework/docs/dev/controllers/
 */
class guestbook2FrontendConfirmAction extends waViewAction
{
    public function execute()
    {
        // Задаём лайаут для фронтенда
        $this->setLayout(new guestbook2FrontendLayout());

        // Получаем hash из GET параметров
        $hash = waRequest::get('hash');
        // Проверяем хэш
        if (!$hash || strlen($hash) < 33) {
            $this->redirect(wa()->getRouteUrl('/frontend'));
        }
        // Получаем contact_id из хэша
        $contact_id = substr($hash, 16, -16);
        $hash = substr($hash, 0, 16).substr($hash, -16);
        $contact = new waContact($contact_id);
        // Проверяем валидность хэша
        if ($contact->getSettings($this->getAppId(), 'confirm_hash') === $hash) {
            // Удаляем хэш
            $contact->delSettings($this->getAppId(), 'confirm_hash');
            // Выставляем статус confirmed для email-адреса контакта
            $contact['email'] = array(
                'value' => $contact->get('email', 'default'),
                'status' => 'confirmed'
            );
            // Сохраняем контакт
            $contact->save();
        } else {
            // Если хэш неправильный, то просто редирект на главную страницу
            $this->redirect(wa()->getRouteUrl('/frontend'));
        }
    }
}