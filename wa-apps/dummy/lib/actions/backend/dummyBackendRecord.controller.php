<?php 

/**
 * JSON controller for retrieving data associated with a certain record
 * This class extends waJsonController
 * Read more about actions and controllers at
 * http://www.webasyst.com/framework/docs/dev/controllers/
 * 
 * Processes requests in backend at URL dummy/?action=record&id=[ID]
 * Read more about request routing in backend at
 * http://www.webasyst.com/framework/docs/dev/backend-routing/
 *
 * Json-контролллер для получения данных о конкретной записи
 * Класс унаследован от waJsonController
 * Подробнее про экшены и контроллеры:
 * http://www.webasyst.com/ru/framework/docs/dev/controllers/
 * 
 * Доступен в бэкенде по урлу dummy/?action=record&id=[ID]
 * Подробнее о маршрутизации в бэкенде:
 * http://www.webasyst.com/ru/framework/docs/dev/backend-routing/
 */
class dummyBackendRecordController extends waJsonController
{
	public function execute()
	{
		// Retrieving id from the GET request (0 – default value, 'int' – data type)
		// Получаем id из GET запроса (0 - значение по умолчанию, 'int' - тип)
		$id = waRequest::get('id', 0, 'int');
		// Retrieving all records
		// Получаем все записи
		$records = $this->getConfig()->getRecords();
		// Verifying existence of record data 
		// Проверяем наличие записи
		if (isset($records[$id])) {
			// Verifying access rights to the record
			// Проверяем права на запись
			if (!$this->getRights('record.'.$id)) {
				// Throwing exception with response code 403 (access denied)
				// Кидаем исключение с кодом 403 (доступ запрещен)
				throw new waException("You don't have permission to access this record.", 403);
			}
			// Returning record data
			// Возвращаем данные записи
			$this->response = $records[$id];
		} else {
			// Throwing exception with response code 404 (record not found)
			// Кидаем исключение с кодом 404 (запись не найдена)
			throw new waException("The requested record was not found.", 404);
		}
	}
}