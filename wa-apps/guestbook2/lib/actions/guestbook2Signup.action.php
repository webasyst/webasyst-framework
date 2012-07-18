<?php

/**
 * Экшн регистрации /signup
 */
class guestbook2SignupAction extends waSignupAction
{
    public function execute()
    {
        // устанавливаем лайаут фронтенда
        $this->setLayout(new guestbook2FrontendLayout());
        $this->setThemeTemplate('signup.html');
        // запускаем выполнение родительского метода
        parent::execute();
    }

    /**
     * Этот метод вызывается после успешного создания нового контакта
     * В нём будет отправлено приветственное письмо новому пользователю
     * @param waContact $contact
     */
    public function afterSignup(waContact $contact)
    {
        // Добавляем контакт в системную категорию guestbook2 (по ID приложения)
        // Чтобы в приложении контакты можно было легко посмотреть все контакты,
        // которые были зарегистрированы в гостевой книге, либо что-то написали в ней
        $contact->addToCategory($this->getAppId());
        // Получаем главный email контакта
        $email = $contact->get('email', 'default');
        // Если он не задан, ничего не делаем
        if (!$email) {
            return;
        }
        // Генерируем случайный хэш
        $hash = md5(uniqid(time(), true));
        // Сохраняем этот хэш в таблице свойств контакта, указывая приложение
        $contact->setSettings($this->getAppId(), 'confirm_hash', $hash);
        // Добавляем в хэш номер контакта, чтобы было проще искать и проверять по хэшу (см. guestbook2FrontendConfirmAction)
        $hash = substr($hash, 0, 16).$contact->getId().substr($hash, 16);
        // Формируем абсолютную ссылку подтверждения
        $confirmation_url = wa()->getRouteUrl('/frontend/confirm', true)."?hash=".$hash;
        // Формируем абсолютную ссылку на главную страницу приложения
        $root_url = wa()->getRouteUrl('/frontend', true);
        // Получаем название аккаунта
        $app_settings_model = new waAppSettingsModel();
        $account_name = htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'));
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

        // Отправляем письмо
        $message = new waMailMessage($subject, $body);
        $message->setTo($email, $contact->getName());
        $message->send();
    }

}