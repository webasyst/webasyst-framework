<?php

/**
 * Controller for processing registrations/authorizations via social networks
 * Контроллер обработки регистраций/входов через социальные сети
 * @see https://www.webasyst.com/framework/docs/dev/auth-adapters/
 */
class guestbook2OAuthController extends waOAuthController
{
    /**
     * This method is called upon successful authorization via a social network
     * Этот метод вызывается после успешной авторизации через соц сети
     * @param array $data
     * @return waContact
     */
    public function afterAuth($data)
    {
        $contact = parent::afterAuth($data);
        // If contact has been successfully authorized and is not a backend user
        // Если контакт был успешно авторизован и он не является юзером бэкенда
        if ($contact && !$contact['is_user']) {
            // Adding contact to system category of the guestbook 2 app
            // Добавляем контакт в системную категорию гостевой книги 2
            $contact->addToCategory($this->getAppId());
        }
        return $contact;
    }
}