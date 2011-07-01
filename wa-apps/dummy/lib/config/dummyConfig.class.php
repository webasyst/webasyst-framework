<?php 

/**
 * Файл конфиг приложения (необязателен)
 * Для приложения пустышка в него добавим метод для получения записей
 * Должен быть унаследован waAppConfig
 * В экшенах и контроллер записи можно будет легко получить:
 * $this->getConfig()->getRecords()
 */
class dummyConfig extends waAppConfig
{
	// переменная для хранения загруженных записей
	protected $records = null;
	/**
	 * Метод возвращающий все записи
	 * 
	 * @param bool $check_rights - если true, то возвращаются только те записи, на которые есть права
	 * @return array - массив записей
	 */
	public function getRecords($check_rights = false)
	{
		if ($this->records === null) {
			$this->records = include(dirname(__FILE__).'/records.php');
		}
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