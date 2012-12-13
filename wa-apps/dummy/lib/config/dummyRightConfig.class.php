<?php

/**
 * Class containing app's access rights settings description
 * http://www.webasyst.com/framework/docs/dev/access-rights/
 *
 * Класс для описания настроек прав доступа для приложения
 * http://www.webasyst.com/ru/framework/docs/dev/access-rights/
 */
class dummyRightConfig extends waRightConfig
{
	/**
	 * In method init all access rights settings of the app must be defined
	 * В методе init должны быть заданы все настройки прав для приложения
	 */
	public function init()
	{
		// Right to add new records
		// Право на создание новых записей
		$this->addItem('add', _w('Add new records'), 'checkbox');
		// Retrieving all records
		// Получаем все записи
		$records = include(dirname(__FILE__).'/records.php');
		// Creating an associative array of records of the form array(id => name, ...) 
		// Формируем ассоциативный массив записей вида array(id => name, ...) 
		foreach ($records as &$r) {
			$r = $r['title'];
		}
		// Adding ability to assign access rights to records
		// Добавляем возможность устанавливать права на записи
    	// For record 1 access rights value will be stored with key record.1
    	// Для записи 1 право будет храниться с ключом record.1
    	$this->addItem('record', _w('Records'), 'list', array('items' => $records));		
	}
}