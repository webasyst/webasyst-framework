<?php

/**
 * Deletion of guestbook records
 * Удаление записи из гостевой книги
 *
 * URL: ?action=delete&id=$id
 */
class guestbook2BackendDeleteController extends waController
{
    public function execute()
    {
        // If user has access rights to delete guestbook records
        // Если у пользователя есть права на удаление записей из гостевой книги
        if ($this->getRights('delete')) {
            // Getting id of the record to be deleted
            // Получаем id удаляемой записи
            $id = waRequest::get('id', 0, 'int');
            if ($id) {
                // Delete record from the database table
                // Удаляем запись из таблицы
                $model = new guestbook2Model();
                $model->deleteById($id);
            }
        }
        // Redirecting user to the app's home page
        // Редирект на главную страницу приложения
        $this->redirect(wa()->getAppUrl());
    }
}