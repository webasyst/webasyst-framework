<?php

class contactsRightsModel extends waModel
{
    protected $table = 'contacts_rights';

    /**
     * Returns rights of user (user_id) to the contacts from contact_id
     *
     * @param int|null $user_id
     * @param int|array $contact_id
     * @return string|boolean rights ('read', 'write' or false)
     */
    public function getRight($user_id, $contact_id)
    {
        if ($user_id) {
            $user = new waUser($user_id);
        } else {
            $user = wa()->getUser();
        }

        if ($user->getRights('contacts', 'category.all')) {
            return 'write';
        }

        $groups = $user->getGroupIds(true);
        $sql = "SELECT cc.contact_id, MAX(r.writable) FROM wa_contact_categories cc
                JOIN ".$this->table." r ON r.category_id = cc.category_id
                WHERE r.group_id IN (i:groups) AND cc.contact_id IN (i:contact_id)
                GROUP BY cc.contact_id";

        $data = $this->query($sql, array('groups' => $groups, 'contact_id' => $contact_id))->fetchAll('contact_id', TRUE);
        if (is_array($contact_id)) {
            foreach ($contact_id as $c) {
                if (isset($data[$c])) {
                    $data[$c] = $data[$c] ? 'write' : 'read';
                } else {
                    $data[$c] = false;
                }
            }
            return $data;
        } else {
            return isset($data[$contact_id]) ? ($data[$contact_id] ? 'write' : 'read') : false;
        }
    }

    /** Get list of categories available for user.
     * @param int $user_id defaults to current auth user
     * @return array|boolean category_id => read|write or TRUE for admins */
    public function getAllowedCategories($user_id = null) {
        if ($user_id) {
            $user = new waUser($user_id);
        } else {
            $user = wa()->getUser();
        }

        if ($user->getRights('contacts', 'category.all')) {
            return true;
        }

        // Not admin, query for categories
        $groups = $user->getGroupIds(true);
        $sql = "SELECT category_id, MAX(writable)
                FROM `{$this->table}`
                WHERE group_id IN (i:groups)
                GROUP BY category_id";
        $result = $this->query($sql, array('groups' => $groups))->fetchAll('category_id', true);
        foreach($result as &$v) {
            $v = $v ? 'write' : 'read';
        }
        return $result;
    }
}

// EOF