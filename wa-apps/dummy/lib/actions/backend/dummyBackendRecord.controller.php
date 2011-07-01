<?php 

/**
 * Json-контролллер для получения данных о конкретной записи
 * Класс унаследован от waJsonController
 * Подробнее про экшены и контроллеры:
 * http://www.webasyst.com/ru/framework/docs/application-guide/controllers/
 * 
 * Доступен в бэкенде по урлу dummy/?action=record&id=[ID]
 * Подробнее о маршрутизации в бэкенде:
 * http://www.webasyst.com/ru/framework/docs/application-guide/backend-routing/
 */
class dummyBackendRecordController extends waJsonController
{
	public function execute()
	{
		// Получаем id из GET запроса (0 - значение по умолчанию, 'int' - тип)
		$id = waRequest::get('id', 0, 'int');
		// Получаем все записи
		$records = $this->getConfig()->getRecords();
		// Проверяем наличие записи
		if (isset($records[$id])) {
			// Проверяем права на запись
			if (!$this->getRights('record.'.$id)) {
				// Кидаем исключение с кодом 403 (доступ запрещен)
				throw new waException("You don't have permission to access this record.", 403);
			}
			// Возвращаем данные записи
			$this->response = $records[$id];
		} else {
			// Кидаем исключение с кодом 404 (запись не найдена)
			throw new waException("The requested record was not found.", 404);
		}
	}
}