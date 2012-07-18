<?php

/**
 * Контроллер обработки регистраций/входов через социальные сети
 */
class guestbook2OAuthController extends waOAuthController
{
    /**
     * Этот метод вызывается после успешной авторизации через соц сети
     * @param array $data
     * @return waContact
     */
    public function afterAuth($data)
    {
        $contact = parent::afterAuth($data);
        // Если контакт был успешно авторизован и он не является юзером бэкенда
        if ($contact && !$contact['is_user']) {
            // Добавляем контакт в системную категорию гостевой книги 2
            $contact->addToCategory($this->getAppId());
        }
        return $contact;
    }
}