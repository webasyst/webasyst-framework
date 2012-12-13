<?php

/**
 * Backend of the Guestbook 2 application
 * Бекенд приложения Гостевая книга 2
 * @see http://www.webasyst.com/framework/docs/dev/backend-routing/
 * @see http://www.webasyst.com/framework/docs/dev/controllers/
 */
class guestbook2BackendAction extends waViewAction
{
    public function execute()
    {

        $this->setLayout(new guestbook2BackendLayout());

        // Creating a model instance for retrieving data from the database
        // Создаем экземпляр модели для получения данных из БД
        $model = new guestbook2Model();
        // Retrieving all guestbook records from the database
        // Получаем все записи гостевой книги из БД
        $records = $model->getRecords(0, 0);
        foreach ($records as &$r) {
            if ($r['contact_id']) {
                $r['name'] = $r['contact_name'];
                // getting the contact photo URL
                // получаем URL на фотографию контакта
                $r['photo_url'] = waContact::getPhotoUrl($r['contact_id'], $r['photo'], 20);
            }
        }
        unset($r);
        // Passing records to the template
        // Передаем записи в шаблон
        $this->view->assign('records', $records);
        // Passing the frontend URL to the template
        // Передаём в шаблон УРЛ фронтенда
        $this->view->assign('url', wa()->getRouteUrl($this->getAppId(), true));

        // Passing user's record deletion access rights value to the template
        // Access rights are defined in config file lib/config/guestbookRightConfig.class.php
        
        // Передаём в шаблон права пользователя на удаление записей из гостевой книги
        // Права описаны в конфиге lib/config/guestbookRightConfig.class.php
        $this->view->assign('rights_delete', $this->getRights('delete'));

        // If user is an admin of the Contacts app then show links to Contacts
        // Если пользователь админ приложения контакты, то показывать ссылки на контакты
        $this->view->assign('rights_contacts', $this->getUser()->isAdmin('contacts'));
    }
}