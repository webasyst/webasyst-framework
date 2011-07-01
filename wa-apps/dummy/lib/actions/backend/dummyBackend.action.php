<?php 

/**
 * Экшен бекенда по умолчанию
 * Подробнее о экшенах и контроллерах:
 * http://www.webasyst.com/ru/framework/docs/application-guide/controllers/
 * 
 * Доступен в бэкенде по урлу dummy/
 * Подробнее о маршрутизации в бэкенде:
 * http://www.webasyst.com/ru/framework/docs/application-guide/backend-routing/
 */
class dummyBackendAction extends waViewAction
{
	/**
	 * Это "входная точка" экшена
	 * Здесь должна быть реализована вся бизнес-логика и подготовлены данные для шаблона
	 */ 
	public function execute()
	{
		// Получаем право на добавление новых записей
		$right_add = $this->getRights('add');
		// Передаем данные в шаблон (actions/backend/Backend.html)
		$this->view->assign('right_add', $right_add);
		$this->view->assign('records', $this->getConfig()->getRecords(true));
	}
}