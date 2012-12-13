<?php 

class guestbookBackendActions extends waViewActions
{
	/**
	 * Default action displaying all guestbook records
	 * Действие по умолчанию, вывод всех записей из гостевой книги
	 *
	 * URL: guestbook/
	 */	
	public function defaultAction()
	{
		// Creating a model instance for retrieving data from the database
		// Создаем экземпляр модели для получения данных из БД 
		$model = new guestbookModel();
		// Retrieving guestbook records from the database
		// Получаем записи гостевой книги из БД
		$records = $model->order('datetime DESC')->fetchAll();
		// Passing records to the template
		// Передаем записи в шаблон
		$this->view->assign('records', $records);	
		// Passing frontend URL to the template
		// Передаём в шаблон УРЛ фронтенда
		$this->view->assign('url', wa()->getRouting()->getUrl('guestbook', true));
		/*
		 * Passing user's access rights to delete records to the template
		 * Access rights are defined in config file lib/config/guestbookRightConfig.class.php
		 *
		 * Передаём в шаблон права пользователя на удаление записей из гостевой книги
		 * Права описаны в конфиге lib/config/guestbookRightConfig.class.php
		 */
		$this->view->assign('rights_delete', $this->getRights('delete'));
	}
	
	/**
	 * Deleting a record from the guestbook
	 * Удаление записи из гостевой книги
	 *
	 * URL: guestbook/?action=delete&id=$id
	 */
	public function deleteAction()
	{
		// If user has access rights to delete records from the guestbook
		// Если у пользователя есть права на удаление записей из гостевой книги
		if ($this->getRights('delete')) {
			// Retrieving id of the record to be deleted
			// Получаем id удаляемой записи
			$id = waRequest::get('id', 0, 'int');
			if ($id) {
				// Deleting the record from the database
				// Удаляем запись из таблицы
				$model = new guestbookModel();
				$model->deleteById($id);
			}
		}
		// Redirecting user to the app's home page
		// Редирект на главную страницу приложения
		$this->redirect(wa()->getAppUrl());
	}
}