<?php 

/**
 * Экшен фронтенда по умолчанию
 * Подробнее о экшенах и контроллерах:
 * http://www.webasyst.com/ru/framework/docs/dev/controllers/
 * 
 * Подробнее о маршрутизации во фронтенде:
 * http://www.webasyst.com/ru/framework/docs/dev/routing/
 */
class dummyFrontendAction extends waViewAction
{
	public function execute()
	{
		// Передаем данные в шаблон (actions/frontend/Frontend.html)
		$this->view->assign('message', 'Hello world!');
	}
}