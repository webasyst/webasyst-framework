<?php

class waContactModel extends waModel
{
    protected $table = "wa_contact";

    /**
     * Returns name/names of specified contact/contacts
     *
     * @param int|array $id Contact id or array of ids
     * @return string|array If $id is array, return associative array with ids as keys and contact names as values
     */
    public function getName($id)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE id ";
        if (is_array($id)) {
            $id = array_unique($id);
            $sql .= " IN ('".implode("','", $this->escape($id, 'int'))."')";
            $rows = $this->query($sql)->fetchAll();
            $result = array();
            foreach ($rows as $row) {
                $result[$row['id']] = waContactNameField::formatName($row);
            }
            return $result;
        } else {
            $sql .= " = i:id";
            $row = $this->query($sql, array('id' => $id))->fetch();
            if ($row) {
                return waContactNameField::formatName($row);
            }
            return '';
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
     * Delete one or more contacts and fire event contacts.delete
     *
     * @event contacts.delete
     *
     * @param int|array $id - contact id or array of contact ids
     * @return bool
     */
    public function delete($id, $send_event=true)
    {
        if ($send_event) {
            // Fire @event contacts.delete allowing other applications to clean up their data
            if (!is_array($id)) {
                $id = array($id);
            }
            wa()->event(array('contacts', 'delete'), $id);
        }

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

        // Delete from contact rights
        if (class_exists('contactsRightsModel')) {
            $contact_rights_model = new contactsRightsModel();
            $contact_rights_model->deleteByField('group_id', $nid);
        }

        // Clean tied verification channel assets
        $verification_channel_assets_model = new waVerificationChannelAssetsModel();
        $verification_channel_assets_model->clearByContact($id);

        // Delete settings
        $setting_model = new waContactSettingsModel();
        $setting_model->deleteByField('contact_id', $id);

        // Delete app tokens
        $app_tokens_model = new waAppTokensModel();
        $app_tokens_model->deleteByField('contact_id', $id);

        // Delete emails
        $contact_email_model = new waContactEmailsModel();
        $contact_email_model->deleteByField('contact_id', $id);

        // Delete from groups
        $user_groups_model = new waUserGroupsModel();
        $user_groups_model->deleteByField('contact_id', $id);

        // Delete data
        $contact_data_model = new waContactDataModel();
        $contact_data_model->deleteByField('contact_id', $id);

        $contact_data_text_model = new waContactDataTextModel();
        $contact_data_text_model->deleteByField('contact_id', $id);

        // Dalete from categories
        $contact_categories_model = new waContactCategoriesModel();
        $category_ids = array_keys($contact_categories_model->getByField('contact_id', $id, 'category_id'));
        $contact_categories_model->deleteByField('contact_id', $id);

        // update counters in wa_contact_category
        $contact_category_model = new waContactCategoryModel();
        $contact_category_model->recalcCounters($category_ids);

        // Delete calendar events
        $contact_events_model = new waContactEventsModel();
        $contact_events_model->deleteByField('contact_id', $id);

//        // Delete contact from logs
//        $login_log_model = new waLoginLogModel();
//        $login_log_model->deleteByField('contact_id', $id);

        // Clear references
        $this->updateByField(array(
            'company_contact_id' => $id
        ), array(
            'company_contact_id' => 0
        ));

        // Delete contact
        return $this->deleteById($id);
    }

    /**
     * @param string $email
     * @param bool|null $with_password With OR Without password OR ignore that condition
     *   Default - ignore password condition
     * @return array
     */
    public function getByEmail($email, $with_password = null)
    {
        $sql = "SELECT c.* FROM `{$this->table}` c 
                JOIN `wa_contact_emails` e ON c.id = e.contact_id
                WHERE e.email = s:0";
        if ($with_password !== null) {
            if ($with_password) {
                $sql .= " AND c.password != ''";
            } else {
                $sql .= " AND c.password = ''";
            }
        }
        $sql .= ' LIMIT 1';
        return $this->query($sql, $email)->fetch();
    }

    /**
     * @param string $phone
     * @param bool|null $with_password With OR Without password OR ignore that condition
     *   Default - ignore password condition
     * @return array
     */
    public function getByPhone($phone, $with_password = null)
    {
        $phone = waContactPhoneField::cleanPhoneNumber($phone);
        $sql = "SELECT c.* FROM `{$this->table}` c 
                JOIN `wa_contact_data` d ON c.id = d.contact_id
                WHERE d.field = 'phone' AND d.value = s:0";
        if ($with_password !== null) {
            if ($with_password) {
                $sql .= " AND c.password != ''";
            } else {
                $sql .= " AND c.password = ''";
            }
        }
        $sql .= ' LIMIT 1';
        return $this->query($sql, $phone)->fetchAssoc();
    }


    public function getByGroups($groups)
    {
        if (is_array($groups) && $groups) {
            $sql = "SELECT c.*
                    FROM wa_contact c
                        JOIN wa_user_groups g
                            ON g.contact_id=c.id
                    WHERE g.group_id IN (?)
                    GROUP BY c.id";
            return $this->query($sql, array($groups))->fetchAll('id');
        } else {
            return array();
        }
    }

    /**
     * Generate unique login by email
     * @param string $email
     * @return string|null
     * @throws waException
     */
    public function generateLoginByEmail($email)
    {
        $validator = new waEmailValidator();
        if (!$validator->isValid($email)) {
            return null;
        }

        $parts = explode('@', $email, 2);

        $email_part = trim($parts[0]);

        $login = $email_part;
        while ($this->getByField(['login' => $login])) {
            $padding = waUtils::getRandomHexString(6);
            $login = $email_part . $padding;
        }

        return $login;
    }
}

// EOF
