<?php

class contactsRightsModel extends waModel
{
    protected $table = 'contacts_rights';

    public function getRight($user_id, $contact_id)
    {
        if ($user_id) {
            $user = new waUser($user_id);
        } else {
            $user = wa()->getUser();
        }
        $user_id = $user->getId();

        if ($user->getRights('contacts', 'edit')) {
            $data = array();
            foreach ((array)$contact_id as $c_id) {
                $u = new waUser($c_id);
                $data[$c_id] = $user->isAdmin() || !$u->isAdmin() ? 'write' : 'read';
            }
        } else {
            $data = array_fill_keys((array)$contact_id, 'read');

            $m = new waContactModel();
            $allowed = array_keys(
                    $m->select('id')->
                        where("create_contact_id = {$user_id}
                    AND id IN(".  implode(',', (array) $contact_id) . ")")
                        ->fetchAll('id')
                    );

            foreach ($allowed as $c) {
                if (isset($data[$c])) {
                    $data[$c] = 'write';
                } else {
                    $data[$c] = false;
                }
            }
        }

        if (is_array($contact_id)) {
            return $data;
        } else {
            return isset($data[$contact_id]) ? $data[$contact_id] : false;
        }
    }

    /** Get list of categories available for user.
     * @deprecated
     * @param int $user_id defaults to current auth user
     * @return array|boolean category_id => read|write or TRUE for admins
     */
    public function getAllowedCategories($user_id = null) {

        return true;

    }

    /**
     * Get list of contacts (IDs) and remove not allowed contacts
     * @param type $contact_ids
     */
    public function getAllowedContactsIds(array $contact_ids)
    {
        $contact_ids = array_map('intval', $contact_ids);
        if (wa()->getUser()->getRights('contacts', 'edit', true)) {
            return $contact_ids;
        }
        if (!$contact_ids) {
            return array();
        }
        $m = new waContactModel();
        $user_id = wa()->getUser()->getId();
        return array_keys(
                $m->select('id')->
                    where("create_contact_id = {$user_id}
                AND id IN(".  implode(',', $contact_ids) . ")")
                    ->fetchAll('id')
                );
    }

}

// EOF