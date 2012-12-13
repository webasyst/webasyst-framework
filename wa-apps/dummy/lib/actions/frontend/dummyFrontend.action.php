<?php 

/**
 * Default frontend action
 * Read more about actions and controllers at
 * http://www.webasyst.com/framework/docs/dev/controllers/
 * 
 * Read more about request routing in frontend at
 * http://www.webasyst.com/framework/docs/dev/routing/
 *
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
		// Passing data to template (actions/frontend/Frontend.html)
		// Передаем данные в шаблон (actions/frontend/Frontend.html)
		$this->view->assign('message', 'Hello world!');
	}
}