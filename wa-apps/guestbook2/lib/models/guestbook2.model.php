<?php

/**
 * Model for managing the database table of guestbook2
 * Модель для работы с таблицей guestbook2
 * @see http://www.webasyst.com/ru/framework/docs/dev/model/
 */
class guestbook2Model extends waModel
{
    /**
     * @var string table name | имя таблицы
     */
    protected $table = 'guestbook2';

    /**
     * Returns guestbook records
     * Возвращает записи гостевой книги
     *
     * @param int $offset - which record to start with (for page-by-page navigation) | начиная с какой записи (для постраничного просмотра)
     * @param int $limit - limit (how many records will be returned) | лимит (сколько записей возвращать)
     * @return array - array of records | массив записей
     */
    public function getRecords($offset = 0, $limit = 20)
    {
        // also retrieving contact_name and photo to display contact's name and photo
        // также получаем contact_name и photo для показа имени и фотографии контакта
        $sql = "SELECT g.*, c.name contact_name, c.photo
                FROM ".$this->table." g
                LEFT JOIN wa_contact c ON g.contact_id = c.id
                ORDER BY datetime DESC";
        if ($limit) {
            $sql .= " LIMIT i:offset, i:limit";
        }
        return $this->query($sql, array('offset' => $offset, 'limit' => $limit))->fetchAll();
    }
}