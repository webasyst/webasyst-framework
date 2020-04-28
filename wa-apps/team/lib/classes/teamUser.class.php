<?php

class teamUser
{
    protected static $current_user = null;

    /**
     * Returns waContact if we're currently on a contact profile page.
     * Otherwise throw an exception.
     */
    public static function getCurrentProfileContact()
    {
        if (self::$current_user) {
            return self::$current_user;
        }

        try {
            // Look up by login if specified via routing
            $user_login = urldecode(waRequest::param('login', null, waRequest::TYPE_STRING_TRIM));
            if ($user_login) {
                self::$current_user = waUser::getByLogin($user_login);
                if (!self::$current_user) {
                    throw new waException('Contact does not exist', 404);
                }
                return self::$current_user;
            }

            // Look up by id if speficied via routing or in get/post
            $user_id = waRequest::param('id', waRequest::request('id', 0, waRequest::TYPE_INT), waRequest::TYPE_INT);
            if ($user_id) {
                self::$current_user = new waContact($user_id);
                self::$current_user->getName(); // throws 404 right away
                return self::$current_user;
            }
        } catch (Exception $e) {
            if ($e->getCode() == 404 && !waSystemConfig::isDebug()) {
                throw new waException('Contact does not exist', 404);
            } else {
                throw $e;
            }
        }

        throw new waException('user not specified', 400);
    }

    public static function getFields($set = 'default')
    {
        if ($set == 'minimal') {
            return 'id,name,firstname,lastname,middlename,login,is_user,photo_url_16,photo_url_32,photo_url_96,birth_day,birth_month';
        } else {
            return 'id,name,firstname,lastname,middlename,company,is_company,is_user,login,locale,jobtitle,last_datetime'
            .',photo_url_16,photo_url_32,photo_url_96,photo_url_144,_event,birth_day,birth_month,_online_status';
        }
    }

    public static function link($u, $absolute = false)
    {
        $config = wa()->getConfig();
        $url = $config->getRootUrl($absolute).$config->getBackendUrl().'/team/';
        if ($u instanceof waContact || is_array($u)) {
            try {
                if (!empty($u['login'])) {
                    return $url.'u/'.$u['login'].'/';
                }
            } catch (waException $e) {
            }
            return $url.'id/'.$u['id'].'/';
        }

        if (wa_is_int($u)) {
            return $url.'id/'.$u.'/';
        }

        return $url.'u/'.$u.'/';
    }

    /**
     * Return list of contacts via waContactsCollection.
     * Takes current user's access rights into account
     *
     * Supported keys for $options array:
     * - fields: 'minimal', 'default' (used when omitted), or comma-separated list of fields as accepted by collection
     * - order: 'from_user_settings' or as accepted by collection method orderBy(), e.g. default 'name ASC'
     * - additional_fields: array(alias => sql expression) as accepted by collection method addField()
     * - add_item_all: boolean, defaults to false; adds a fake contact with id=null, name=_w('All users'), fake photo and all other fields empty
     * - access_rights: boolean (defaults to true: filter out contacts current user has no access to)
     * - offset: default 0
     * - limit: default 100500
     * - fetch_total_count: default false
     *
     *
     * @param string $collection_hash
     * @param array $options
     * @param null &$total_count - output parameter. If $option['fetch_total_count'] == true here will be fetched total count of users
     * @return array
     * @throws waException
     */
    public static function getList($collection_hash, $options = array(), &$total_count = null)
    {
        $collection = new teamUsersCollection($collection_hash);

        // Additional fields
        foreach (ifset($options['additional_fields'], array()) as $alias => $field) {
            $collection->addField($field, $alias);
        }

        // Order by
        $order_by = ifset($options['order']);
        if ($order_by === 'from_user_settings') {
            $order_by = wa()->getUser()->getSettings(wa()->getApp(), 'sort', 'last_seen');
            if (!$order_by) {
                $order_by = 'last_seen';
            }
        }

        switch ($order_by) {
            case 'signed_up':
                $order_by = 'create_datetime DESC';
                break;
            case 'last_seen':
                $order_by = 'last_datetime DESC';
                break;
            default:
                $order_by = 'name ASC';
                break;
        }
        
        $order_by = explode(' ', $order_by);
        $order_by[1] = strtoupper(ifset($order_by[1], 'ASC'));

        if ($order_by[0] == 'name') {
            $collection->orderBy('_display_name', $order_by[1]);
        } else {
            $collection->orderBy($order_by[0], $order_by[1]);
        }

        // Fields
        $fields = ifset($options['fields'], 'default');
        if ($fields === 'default' || $fields === 'minimal') {
            $fields = teamUser::getFields($fields);
        }

        // Fetch contacts
        $contacts = $collection->getContacts($fields, ifset($options['offset'], 0), ifset($options['limit'], 100500));

        // Fetch counter
        if (!empty($options['fetch_total_count'])) {
            $total_count = $collection->count();
        }

        // Filter out contacts not visible because of access rights to groups
        if (ifset($options['access_rights'], true)) {
            teamUser::keepVisible($contacts, ifset($options['can_edit']));
        }

        // Convert single date time to UTC
        if (!empty($options['convert_to_utc'])) {
            teamUser::convertFieldToUtc($contacts, $options['convert_to_utc']);
        }

        // Format names as set up in app settings
        foreach ($contacts as &$u) {
            $u['name'] = waUser::formatName($u);
        }
        unset($u);

        // Sort by actual name
        if ($order_by[0] === 'name') {
            if ($order_by[1] === 'DESC') {
                $callback = wa_lambda('$a, $b', 'return -strcmp($a["name"], $b["name"]);');
            } else {
                $callback = wa_lambda('$a, $b', 'return strcmp($a["name"], $b["name"]);');
            }
            uasort($contacts, $callback);
        }

        // Fake contact 'All users'
        if (!empty($options['add_item_all'])) {
            $contacts = array(
                'all' => array(
                    'id' => null,
                    'name' => _w('All users'),
                    'photo_url_16' => wa()->getRootUrl().'wa-content/img/userpic20.jpg',
                    'photo_url_32' => wa()->getRootUrl().'wa-content/img/userpic32.jpg',
                ) + array_fill_keys(array_map('trim', explode(',', $fields)), ''),
            ) + $contacts;
        }

        return $contacts;
    }

    public static function createContactByEmail($email, $data = null, $create_method = 'invite')
    {
        if (waConfig::get('is_template')) {
            return false;
        }
        $c = new waContact();
        $c->save(array(
            'email'         => array($email),
            'create_method' => $create_method,
            'locale'        => wa()->getLocale(),
        ));
        if (!$c->getId()) {
            return false;
        }
        return self::createContactToken($c->getId(), $data);
    }

    public static function createContactToken($contact_id, $data = null)
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $app_tokens_model = new waAppTokensModel();
        return $app_tokens_model->add(array(
            'app_id'            => 'team',
            'type'              => 'user_invite',
            'contact_id'        => $contact_id,
            'create_contact_id' => wa()->getUser()->getId(),
            'expire_datetime'   => date('Y-m-d H:i:s', time() + 3600 * 24 * 3),
            'create_datetime'   => date('Y-m-d H:i:s'),
            'data'              => json_encode($data),
        ));
    }

    public static function canEdit($contact_id, $user = null)
    {
        try {
            $user = ifset($user, wa()->getUser());
            $user_id = $user->getId();

            // Admin can edit whoever they want
            if ($user->isAdmin('team')) {
                return true;
            }

            if ($contact_id instanceof waContact) {
                $contact = $contact_id;
                $contact_id = $contact->getId();
            } else {
                $contact = new waContact($contact_id);
            }

            // User can edit contacts he added recently
            if ($contact['create_contact_id'] == $user_id && strtotime($contact['create_datetime']) > time() - 3600) {
                return true;
            }

            // Own profile?
            if ($user_id == $contact_id) {
                if ($user->getRights('team', 'backend')) {
                    // Can always edit self if have access to Team app
                    return true;
                } else {
                    // System profile allows only limited number of fields
                    return 'limited_own_profile';
                }
            }

            // Non-users are not editable by non-admins
            if ($contact['is_user'] <= 0) {
                return false;
            }

            $user_groups_model = new waUserGroupsModel();
            $contact_group_ids = array_fill_keys($user_groups_model->getGroupIds($contact_id), 1);

            // Users not within groups are not editable by non-admins
            if (!$contact_group_ids) {
                return false;
            }

            // User can edit users in certain groups
            $user_can_edit_groups = $user->getRights('team', 'manage_users_in_group.%');
            $user_can_edit_groups = array_filter($user_can_edit_groups, wa_lambda('$a', 'return $a > 0;'));
            if (array_intersect_key($user_can_edit_groups, $contact_group_ids)) {
                return true;
            }
        } catch (waException $e) {
        }

        return false;
    }

    public static function canDelete($contact_id, $user = null)
    {
        try {
            if ($contact_id instanceof waContact) {
                $contact = $contact_id;
                $contact_id = $contact->getId();
            } else {
                $contact = new waContact($contact_id);
            }

            $user = ifset($user, wa()->getUser());
            $user_id = $user->getId();

            if ($contact['is_user']) {
                return $user->isAdmin();
            } else {
                return $user->isAdmin('team');
            }
        } catch (waException $e) {
        }

        return false;
    }

    // Convert field from server time to UTC in all contacts
    public static function convertFieldToUtc(&$contacts, $field='update_datetime')
    {
        foreach ($contacts as &$c) {
            if ($c[$field] && substr($c[$field], 0, 4) != '0000') {
                $c[$field] = waDateTime::format('Y-m-d H:i:s', $c[$field], 'UTC');
            } else {
                $c[$field] = '';
            }
        }
        unset($c);
    }

    /**
     * Filter out a list of contacts, keeping only those the current user is available to see/edit
     * @param array $contacts array(contact_id => contact_data)
     */
    public static function keepVisible(&$contacts, $editable_only = false)
    {
        // Nothing to filter out if admin
        if (wa()->getUser()->isAdmin('team')) {
            return;
        }

        // It is allowed to see/edit a user if one of the following is true:
        // * user is self
        // * user is visible (but not editable) if not added to any group
        // * user is added to a visible/editable group

        // This thing does caching inside
        $hidden_group_ids = wa()->getUser()->getRights('team', 'manage_users_in_group.%');

        $min_access_level = 0;
        if ($editable_only) {
            $min_access_level = 1;

            // user->getRights() does not return zero-level access, so we have to fetch
            // list of all groups here.
            // (::getWaGroups() does the caching inside)
            $all_groups = array_fill_keys(array_keys(teamHelper::getWaGroups()), 0);
            $hidden_group_ids += $all_groups;
        }

        $hidden_group_ids = array_keys(array_filter($hidden_group_ids, wa_lambda('$a', 'return $a < '.$min_access_level.';')));
        if (!$hidden_group_ids) {
            return;
        }

        // Cache here saves 1-2 queries per page view
        static $contact_groups = array(); // contact_id => list of group_ids
        if (!$contact_groups) {
            // We don't care about our own groups
            $contact_groups[wa()->getUser()->getId()] = array();
        }

        // Load groups for contacts we don't have in cache yet
        $contact_ids = array_keys(array_diff_key($contacts, $contact_groups));
        if ($contact_ids) {
            $user_groups_model = new waUserGroupsModel();
            $contact_groups += $user_groups_model->getGroupIdsForUsers($contact_ids);
            $contact_groups += array_fill_keys($contact_ids, array());
        }

        foreach ($contacts as $id => $c) {
            // User is self?
            if ($id == wa()->getUser()->getId()) {
                continue;
            }
            // User is not added to any group?
            if (!$editable_only && empty($contact_groups[$id])) {
                continue;
            }
            // User is added to at least one visible group?
            if (array_diff($contact_groups[$id], $hidden_group_ids)) {
                continue;
            }
            // Nope, no acces for you.
            unset($contacts[$id]);
        }
    }
}
