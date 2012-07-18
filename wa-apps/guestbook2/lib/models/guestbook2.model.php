<?php

/**
 * Модель для работы с таблицей guestbook2
 * @see http://www.webasyst.com/ru/framework/docs/dev/model/
 */
class guestbook2Model extends waModel
{
    /**
     * @var string имя таблицы
     */
    protected $table = 'guestbook2';

    /**
     * Возвращает записи гостевой книги
     * @param int $offset - начиная с какой записи (для постраничного просмотра)
     * @param int $limit - лимит (сколько записей возвращать)
     * @return array - массив записей
     */
    public function getRecords($offset = 0, $limit = 20)
    {
        // так же получаем contact_name и photo для показа имени и фотографии контакта
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