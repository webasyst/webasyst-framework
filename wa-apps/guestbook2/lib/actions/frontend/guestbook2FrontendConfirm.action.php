<?php

/**
 * Frontend of the Guestbook 2 application
 * Email address confirmation page
 *
 * Фронтенд приложения Гостевая книга 2
 * Страница подтверждения email-адреса
 *
 * @see http://www.webasyst.com/framework/docs/dev/frontend-routing/
 * @see http://www.webasyst.com/framework/docs/dev/controllers/
 */
class guestbook2FrontendConfirmAction extends waViewAction
{
    public function execute()
    {
        // Setting the frontend layout
        // Задаём лайаут для фронтенда
        $this->setLayout(new guestbook2FrontendLayout());

        // Retrieving hash from the GET request
        // Получаем hash из GET параметров
        $hash = waRequest::get('hash');
        // Verifying hash
        // Проверяем хэш
        if (!$hash || strlen($hash) < 33) {
            $this->redirect(wa()->getRouteUrl('/frontend'));
        }
        // Retrieving contact_id from the hash
        // Получаем contact_id из хэша
        $contact_id = substr($hash, 16, -16);
        $hash = substr($hash, 0, 16).substr($hash, -16);
        $contact = new waContact($contact_id);
        // Validating hash
        // Проверяем валидность хэша
        if ($contact->getSettings($this->getAppId(), 'confirm_hash') === $hash) {
            // Deleting hash
            // Удаляем хэш
            $contact->delSettings($this->getAppId(), 'confirm_hash');
            // Setting "confirmed" status to the contact's email address
            // Выставляем статус confirmed для email-адреса контакта
            $contact['email'] = array(
                'value' => $contact->get('email', 'default'),
                'status' => 'confirmed'
            );
            // Saving contact
            // Сохраняем контакт
            $contact->save();
        } else {
            // If the hash is incorrect then simply redirect to the home page
            // Если хэш неправильный, то просто редирект на главную страницу
            $this->redirect(wa()->getRouteUrl('/frontend'));
        }
    }
}