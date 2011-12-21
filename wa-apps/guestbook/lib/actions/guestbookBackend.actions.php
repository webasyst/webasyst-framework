<?php 

class guestbookBackendActions extends waViewActions
{
	/**
	 * Действие по умолчанию, вывод всех записей из гостевой книги
	 * URL: guestbook/
	 */	
	public function defaultAction()
	{
		// Создаем экземпляр модели для получения данных из БД 
		$model = new guestbookModel();
		// Получаем записи гостевой книги из БД
		$records = $model->order('datetime DESC')->fetchAll();
		// Передаем записи в шаблон
		$this->view->assign('records', $records);	
		// Передаём в шаблон УРЛ фронтенда
		$this->view->assign('url', wa()->getRouting()->getUrl('guestbook', true));
		/*
		 * Передаём в шаблон права пользователя на удаление записей из гостевой книги
		 * Права описаны в конфиге lib/config/guestbookRightConfig.class.php
		 */
		$this->view->assign('rights_delete', $this->getRights('delete'));
	}
	
	/**
	 * Удаление записи из гостевой книги
	 * URL: guestbook/?action=delete&id=$id
	 */
	public function deleteAction()
	{
		// Если у пользователя есть права на удаление записей из гостевой книги
		if ($this->getRights('delete')) {
			// Получаем id удаляемой записи
			$id = waRequest::get('id', 0, 'int');
			if ($id) {
				// Удаляем запись из таблицы
				$model = new guestbookModel();
				$model->deleteById($id);
			}
		}
		// Редирект на главную страницу приложения
		$this->redirect(wa()->getAppUrl());
	}
}