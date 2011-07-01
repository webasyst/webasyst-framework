<?php 
/**
 * Экшен фронтенда
 * Здесь реализуется вся логика для фронтенда приложения гостевая книга
 *
 */
class guestbookFrontendAction extends waViewAction
{
	public function execute()
	{
		// Создаем экземпляр модели для получения данных из БД 
		$model = new guestbookModel();
		// Если пришёл POST-запрос, то нужно записать в БД новую запись		
		if (waRequest::method() == 'post') {
			// Получаем данные из POST
			$name = waRequest::post('name');
			$text = waRequest::post('text');
			if ($name && $text) {
				// Вставляем новую запись в таблицу
				$model->insert(array(
					'name' => $name,
					'text' => $text,
					'datetime' => date('Y-m-d H:i:s') 
				));
			}
			$this->redirect();
		}		
		// Получаем записи гостевой книги из БД
		$records = $model->order('datetime DESC')->fetchAll();
		// Передаем записи в шаблон
		$this->view->assign('records', $records);
	}
}