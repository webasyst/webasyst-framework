<?php 

/**
 * Default backend action
 * Read more about actions and controllers at
 * http://www.webasyst.com/framework/docs/dev/controllers/
 * 
 * Processes requests in backend at URL dummy/
 * Read more about request routing in backend at
 * http://www.webasyst.com/framework/docs/dev/backend-routing/
 *
 * Экшен бекенда по умолчанию
 * Подробнее о экшенах и контроллерах:
 * http://www.webasyst.com/ru/framework/docs/dev/controllers/
 * 
 * Доступен в бэкенде по урлу dummy/
 * Подробнее о маршрутизации в бэкенде:
 * http://www.webasyst.com/ru/framework/docs/dev/backend-routing/
 */
class dummyBackendAction extends waViewAction
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
		// Obtaining the right to add new records
		// Получаем право на добавление новых записей
		$right_add = $this->getRights('add');
		// Passing data to template (actions/backend/Backend.html)
		// Передаем данные в шаблон (actions/backend/Backend.html)
		$this->view->assign('right_add', $right_add);
		$this->view->assign('records', $this->getConfig()->getRecords(true));
	}
}