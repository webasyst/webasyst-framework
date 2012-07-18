<?php

/**
 * Бекенд приложения Гостевая книга 2
 * @see http://www.webasyst.com/ru/framework/docs/dev/backend-routing/
 * @see http://www.webasyst.com/ru/framework/docs/dev/controllers/
 */
class guestbook2BackendAction extends waViewAction
{
    public function execute()
    {
        // Создаем экземпляр модели для получения данных из БД
        $model = new guestbook2Model();
        // Получаем все записи гостевой книги из БД
        $records = $model->getRecords(0, 0);
        foreach ($records as &$r) {
            if ($r['contact_id']) {
                $r['name'] = $r['contact_name'];
                // получаем URL на фотографию контакта
                $r['photo_url'] = waContact::getPhotoUrl($r['contact_id'], $r['photo'], 20);
            }
        }
        unset($r);
        // Передаем записи в шаблон
        $this->view->assign('records', $records);
        // Передаём в шаблон УРЛ фронтенда
        $this->view->assign('url', wa()->getRouteUrl($this->getAppId(), true));

        // Передаём в шаблон права пользователя на удаление записей из гостевой книги
        // Права описаны в конфиге lib/config/guestbookRightConfig.class.php
        $this->view->assign('rights_delete', $this->getRights('delete'));

        // Если пользователь админ приложения контакты, то показывать ссылки на контакты
        $this->view->assign('rights_contacts', $this->getUser()->isAdmin('contacts'));
    }
}