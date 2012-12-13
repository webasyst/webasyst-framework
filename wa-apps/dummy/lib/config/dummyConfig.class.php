<?php 

/**
 * App's config file (optional)
 * For the dummy app let's add a method to retrieve records.
 * The class must extend waAppConfig.
 * It will allow you to easily retrieve records in actions and controllers:
 * $this->getConfig()->getRecords()
 *
 * Файл конфиг приложения (необязателен)
 * Для приложения пустышка в него добавим метод для получения записей
 * Должен быть унаследован waAppConfig
 * В экшенах и контроллерах записи можно будет легко получить:
 * $this->getConfig()->getRecords()
 */
class dummyConfig extends waAppConfig
{
	// variable for storing loaded records
	// переменная для хранения загруженных записей
	protected $records = null;
	/**
	 * Method for retrieving all records
	 * Метод возвращающий все записи
	 * 
	 * @param bool $check_rights - if true then only accessible records are returned
	 если true, то возвращаются только те записи, на которые есть права
	 * @return array - array of records
	 массив записей
	 */
	public function getRecords($check_rights = false)
	{
		if ($this->records === null) {
			$this->records = include(dirname(__FILE__).'/records.php');
		}
		// Verifying access rights if necessary and if the user is not an admin
		// Проверяем права если это необходимо и пользователь не админ
		if ($check_rights && !waSystem::getInstance()->getUser()->isAdmin($this->application)) {
			$rights = waSystem::getInstance()->getUser()->getRights($this->application, 'record.%', true);
			$result = array();
			foreach ($this->records as $i => $r) {
				if (!empty($rights[$i])) {
					$result[$i] = $r;
				}
			}
			return $result;
		}
		return $this->records;
	}
}