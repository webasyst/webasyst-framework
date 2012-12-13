<?php

/**
 * This handler is triggered when one or more contacts are deleted
 * The contact_id value of the deleted contact is changed to 0 and the name becomes equal to the contact's name
 *
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
        // Getting all contacts to be deleted
        // Получаем все удаляемые контакты
        $contact_model = new waContactModel();
        $contacts = $contact_model->getByField('id', $params, true);

        $guestbook_model = new guestbook2Model();
        foreach ($contacts as $contact) {
            // Updating guestbook records to avoid appearance of non-existent contact_id values
            // Обновляем записи гостевой книги, чтобы не было "битых" contact_id
            $guestbook_model->updateByField('contact_id', $contact['id'], array(
                'contact_id' => 0,
                'name' => $contact['name']
            ));
        }
    }
}
