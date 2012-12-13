<?php 
/**
 * Frontend action
 * Here the entire frontend logic of the guestbook app is implemented
 *
 * Экшен фронтенда
 * Здесь реализуется вся логика для фронтенда приложения гостевая книга
 *
 */
class guestbookFrontendAction extends waViewAction
{
	public function execute()
	{
		// Creating a model instance for retriving data from the database
		// Создаем экземпляр модели для получения данных из БД
		$model = new guestbookModel();
		// If a POST request is received then a new record is added to the database
		// Если пришёл POST-запрос, то нужно записать в БД новую запись
		if (waRequest::method() == 'post') {
			// Retrieving data from the POST request
			// Получаем данные из POST
			$name = waRequest::post('name');
			$text = waRequest::post('text');
			if ($name && $text) {
				// Inserting a new record into the table
				// Вставляем новую запись в таблицу
				$model->insert(array(
					'name' => $name,
					'text' => $text,
					'datetime' => date('Y-m-d H:i:s') 
				));
			}
			$this->redirect();
		}
		// Retrieving guestbook records from the database
		// Получаем записи гостевой книги из БД
		$records = $model->order('datetime DESC')->fetchAll();
		// Passing records to the template
		// Передаем записи в шаблон
		$this->view->assign('records', $records);
	}
}