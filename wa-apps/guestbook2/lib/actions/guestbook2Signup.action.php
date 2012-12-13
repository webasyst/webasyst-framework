<?php

/**
 * Signup action /signup
 * Экшен регистрации /signup
 * @see https://www.webasyst.com/framework/docs/dev/auth-frontend/
 */
class guestbook2SignupAction extends waSignupAction
{
    public function execute()
    {
        // setting the frontend layout
        // устанавливаем лайаут фронтенда
        $this->setLayout(new guestbook2FrontendLayout());
        $this->setThemeTemplate('signup.html');
        // calling the parent's method
        // запускаем выполнение родительского метода
        parent::execute();
    }

    /**
     * This method is called upon successful creation of a new contact
     * It sends a welcome message to the new user
     *
     * Этот метод вызывается после успешного создания нового контакта
     * В нём будет отправлено приветственное письмо новому пользователю
     *
     * @param waContact $contact
     */
    public function afterSignup(waContact $contact)
    {
        // Adding contact to system category guestbook2 (named by the app ID)
        // to be able to easily view all contacts registered in the guestbook
        // or who have left a comment, in the Contacts app
        
        // Добавляем контакт в системную категорию guestbook2 (по ID приложения)
        // Чтобы в приложении Контакты можно было легко посмотреть все контакты,
        // которые были зарегистрированы в гостевой книге, либо что-то написали в ней
        $contact->addToCategory($this->getAppId());
        // Getting contact's main email address
        // Получаем главный email контакта
        $email = $contact->get('email', 'default');
        // If not specified, do nothing
        // Если он не задан, ничего не делаем
        if (!$email) {
            return;
        }
        // Generating random hash
        // Генерируем случайный хэш
        $hash = md5(uniqid(time(), true));
        // Saving the hash in contact info table with the app id
        // Сохраняем этот хэш в таблице свойств контакта, указывая приложение
        $contact->setSettings($this->getAppId(), 'confirm_hash', $hash);
        // Adding contact id to the hash for easier search and verification by hash (see guestbook2FrontendConfirmAction)
        // Добавляем в хэш номер контакта, чтобы было проще искать и проверять по хэшу (см. guestbook2FrontendConfirmAction)
        $hash = substr($hash, 0, 16).$contact->getId().substr($hash, 16);
        // Creating confirmation link with an absolute URL
        // Формируем абсолютную ссылку подтверждения
        $confirmation_url = wa()->getRouteUrl('/frontend/confirm', true)."?hash=".$hash;
        // Creating a link to the app's home page with an absolute URL
        // Формируем абсолютную ссылку на главную страницу приложения
        $root_url = wa()->getRouteUrl('/frontend', true);
        // Getting account name
        // Получаем название аккаунта
        $app_settings_model = new waAppSettingsModel();
        $account_name = htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'));
        // Generating message body
        // Формируем тело письма
        $body = _w('Hi').' '.htmlspecialchars($contact->getName()).',<br>
<br>
'.sprintf(_w('Please confirm your account at %s by clicking this link:'), $account_name).'<br>
<a href="'.$confirmation_url.'"><strong>'.$confirmation_url.'</strong></a><br>
<br>
--<br>
'.$account_name.'<br>
<a href="'.$root_url.'">'.$root_url.'</a>';
        $subject = _w('Confirm your account');

        // Sending email message
        // Отправляем письмо
        $message = new waMailMessage($subject, $body);
        $message->setTo($email, $contact->getName());
        $message->send();
    }

}