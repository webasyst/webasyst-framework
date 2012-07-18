<?php

/**
 * Удаление записи из гостевой книги
 * URL: ?action=delete&id=$id
 */
class guestbook2BackendDeleteController extends waController
{
    public function execute()
    {
        // Если у пользователя есть права на удаление записей из гостевой книги
        if ($this->getRights('delete')) {
            // Получаем id удаляемой записи
            $id = waRequest::get('id', 0, 'int');
            if ($id) {
                // Удаляем запись из таблицы
                $model = new guestbook2Model();
                $model->deleteById($id);
            }
        }
        // Редирект на главную страницу приложения
        $this->redirect(wa()->getAppUrl());
    }
}