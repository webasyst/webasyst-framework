<?php 

/**
 * Класс для описания настроек прав доступа для приложения
 * http://www.webasyst.com/ru/framework/docs/application-guide/access-rights/
 */
class dummyRightConfig extends waRightConfig
{
	/**
	 * В методе init должны быть заданы все настройки прав для приложения
	 */
	public function init()
	{
		// Право на создание новых записей
		$this->addItem('add', _w('Add new records'), 'checkbox');
		// Получаем все записи
		$records = include(dirname(__FILE__).'/records.php');
		// Формируем ассоциативный массив записей вида array(id => name, ...) 
		foreach ($records as &$r) {
			$r = $r['title'];
		}
		// Добавляем возможность устанавливать права на записи
    	// Для записи 1 право будет храниться с ключом record.1
    	$this->addItem('record', _w('Records'), 'list', array('items' => $records));		
	}
}