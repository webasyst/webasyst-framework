<?php

/**
 * Этот хендлер срабатывает при удалении контакта(ов)
 * При удалении контакта contact_id меняется на 0, а имя проставляется именем контакта
 */
class guestbook2ContactsDeleteHandler extends waEventHandler
{
    /**
     * @param array $params deleted contact_id
     * @see waEventHandler::execute()
     * @return void
     */
    public function execute($params)
    {
        // Получаем все удаляемые контакты
        $contact_model = new waContactModel();
        $contacts = $contact_model->getByField('id', $params, true);

        $guestbook_model = new guestbook2Model();
        foreach ($contacts as $contact) {
            // Обновляем записи гостевой книги, чтобы не было "битых" contact_id
            $guestbook_model->updateByField('contact_id', $contact['id'], array(
                'contact_id' => 0,
                'name' => $contact['name']
            ));
        }
    }
}
