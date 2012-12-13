<?php

/**
 * Backend's action records returning the full record list
 * Read more about actions and controllers at
 * http://www.webasyst.com/framework/docs/dev/controllers/
 * 
 * Processes requests in backend at URL dummy/?action=records
 * Read more about request routing in backend at
 * http://www.webasyst.com/framework/docs/dev/backend-routing/
 *
 * Экшен records бекенда возвращающий список всех записей 
 * Подробнее о экшенах и контроллерах:
 * http://www.webasyst.com/ru/framework/docs/dev/controllers/
 * 
 * Доступен в бэкенде по урлу dummy/?action=records
 * Подробнее о маршрутизации в бэкенде:
 * http://www.webasyst.com/ru/framework/docs/dev/backend-routing/
 */
class dummyBackendRecordsAction extends waViewAction
{
	/**
	 * This is the action's entry point
	 * Here all business logic should be implemented and data for templates should be prepared
	 *
	 * Это "входная точка" экшена
	 * Здесь должна быть реализована вся бизнес-логика и подготовлены данные для шаблона
	 */ 	
	public function execute()
	{
		// Retrieving the list of all records to which the current user has access rights
		// Получаем список всех записей, на который у пользователя есть права
		$records = $this->getConfig()->getRecords(true);
		// Passing data to template (actions/backend/BackendRecords.html)
		// Передаем данные в шаблон (actions/backend/BackendRecords.html)
		$this->view->assign('records', $records);
	}
}