<?php

/**
 * Экшен records бекенда возвращающий список всех записей 
 * Подробнее о экшенах и контроллерах:
 * http://www.webasyst.com/ru/framework/docs/application-guide/controllers/
 * 
 * Доступен в бэкенде по урлу dummy/?action=records
 * Подробнее о маршрутизации в бэкенде:
 * http://www.webasyst.com/ru/framework/docs/application-guide/backend-routing/
 */
class dummyBackendRecordsAction extends waViewAction
{
	/**
	 * Это "входная точка" экшена
	 * Здесь должна быть реализована вся бизнес-логика и подготовлены данные для шаблона
	 */ 	
	public function execute()
	{
		// Получаем список всех записей, на который у пользователя есть права
		$records = $this->getConfig()->getRecords(true);
		// Передаем данные в шаблон (actions/backend/BackendRecords.html)
		$this->view->assign('records', $records);
	}
}