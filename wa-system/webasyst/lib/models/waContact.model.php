<?php

class waContactModel extends waModel
{
    protected $table = "wa_contact";

    /**
     * Возвращает имя/имена указанного контакта/контактов
     *
     * @param int|array $id - число или массив
     * @return string|array - если $id был массивов, возвращает ассоциативный массив с ключем - id, значением - имя контакта
     */
    public function getName($id)
    {
        $sql = "SELECT id, name FROM ".$this->table." WHERE id ";
        if (is_array($id)) {
            $id = array_unique($id);
            $sql .= " IN ('".implode("','", $this->escape($id, 'int'))."')";
            return $this->query($sql)->fetchAll('id', true);
        } else {
            $sql .= " = i:id";
            return $this->query($sql, array('id' => $id))->fetchField('name');
        }
    }

    public function getCompany($id)
    {
        $sql = "SELECT id, company FROM ".$this->table." WHERE id ";
        if (is_array($id)) {
            $id = array_unique($id);
            $sql .= " IN ('".implode("','", $this->escape($id, 'int'))."')";
            return $this->query($sql)->fetchAll('id', true);
        } else {
            $sql .= " = i:id";
            return $this->query($sql, array('id' => $id))->fetchField('company');
        }
    }


    public function insert($data, $type = 0)
    {
        if (!isset($data['create_contact_id'])) {
            $data['create_contact_id'] = waSystem::getInstance()->getUser()->getId();
        }
        if (!isset($data['create_app_id'])) {
            $data['create_app_id'] = waSystem::getInstance()->getApp();
        }
        if (!isset($data['create_datetime'])) {
            $data['create_datetime'] = date("Y-m-d H:i:s");
        }
        return parent::insert($data, $type = 0);
    }

    /**
     * Delete one or more contacts and fire event сontacts.delete
     *
     * @event contacts.delete
     *
     * @param int|array $id - contact id or array of contact ids
     * @return bool
     */
    public function delete($id)
    {
        // Fire @event contacts.delete to delete links to other applications
        $result = wa()->event(array('contacts', 'delete'), $id);


        if (is_array($id)) {
            $nid = array();
            foreach ($id as $i) {
                $nid[] = -(int)$i;
            }
        } else {
            $nid = -(int)$id;
        }

        // Delete rights
        $right_model = new waContactRightsModel();
        $right_model->deleteByField('group_id', $nid);

        // Delete settings
        $setting_model = new waContactSettingsModel();
        $setting_model->deleteByField('contact_id', $id);

        // Delete emails
        $contact_email_model = new waContactEmailsModel();
        $contact_email_model->deleteByField('contact_id', $id);

        // Delete from groups
        $user_groups_model = new waUserGroupsModel();
        $user_groups_model->deleteByField('contact_id', $id);

        // Delete from contact lists
        if (class_exists('contactsContactListsModel')) {
            $contact_lists_model = new contactsContactListsModel();
            $contact_lists_model->deleteByField('contact_id', $id);
        }

        // Delete from contact rights
        $contact_rights_model = new contactsRightsModel();
        $contact_rights_model->deleteByField('group_id', $nid);

        // Delete data
        $contact_data_model = new waContactDataModel();
        $contact_data_model->deleteByField('contact_id', $id);
        
        $contact_data_text_model = new waContactDataTextModel();
        $contact_data_text_model->deleteByField('contact_id', $id);
        

        // Delete contact from logs
        $login_log_model = new waLoginLogModel();
        $login_log_model->deleteByField('contact_id', $id);

        // Delete contact
        return $this->deleteById($id);
    }
}

// EOF