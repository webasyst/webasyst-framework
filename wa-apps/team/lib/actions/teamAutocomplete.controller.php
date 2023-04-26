<?php

class teamAutocompleteController extends waController
{
    protected static $limit = 10;

    public function execute()
    {
        $data = array();
        $q = waRequest::post('term', '', waRequest::TYPE_STRING_TRIM);
        if ($q) {
            $type = waRequest::post('type', 'user', waRequest::TYPE_STRING_TRIM);
            if ($type == 'user') {
                $data = self::usersAutocomplete($q);
            } else if ($type == 'contact') {
                $data = self::contactsAutocomplete($q);
            }
            if ($data) {
                $data = $this->formatData($data, $type);
            }
        }
        echo json_encode($data);
    }

    private function formatData($data, $type)
    {
        if ($type == 'user' && ( $group_id = waRequest::request('group', null, waRequest::TYPE_INT))) {
            $ugm = new waUserGroupsModel();
            $group_users = $ugm->select('contact_id')->where('group_id='.$group_id)->fetchAll('contact_id', true);
            foreach ($data as &$contact) {
                $contact['in_group'] = isset($group_users[$contact['id']]);
            }
            unset($contact);
        }
        return $data;
    }

    public static function usersAutocomplete($q, $limit = null, $only_users = true)
    {
        $m = new waModel();

        $only_users_sql = '';
        if ($only_users) {
            $only_users_sql = ' AND (login IS NOT NULL AND c.is_user<>0)';
        }

        // The plan is: try queries one by one (starting with fast ones),
        // until we find 5 rows total.
        $sqls = array();

        // Name starts with requested string
        $sqls[] = "SELECT c.id, c.name, c.login, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                   WHERE c.name LIKE '".$m->escape($q, 'like')."%' {$only_users_sql}
                   LIMIT {LIMIT}";

        // Login starts with requested string
        $sqls[] = "SELECT c.id, c.name, c.login, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                   WHERE c.login LIKE '".$m->escape($q, 'like')."%' {$only_users_sql}
                   LIMIT {LIMIT}";

        // Email starts with requested string
        $sqls[] = "SELECT c.id, c.name, c.login, e.email, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                       JOIN wa_contact_emails AS e
                           ON e.contact_id=c.id
                   WHERE e.email LIKE '".$m->escape($q, 'like')."%' {$only_users_sql}
                   LIMIT {LIMIT}";

        // Phone contains requested string
        if (preg_match('~^[wp0-9\-\+\#\*\(\)\. ]+$~', $q)) {
            $dq = preg_replace('/[^\d]+/', '', $q);
            $sqls[] = "SELECT c.id, c.name, c.login, d.value as phone, c.firstname, c.middlename, c.lastname, c.photo
                       FROM wa_contact AS c
                           JOIN wa_contact_data AS d
                               ON d.contact_id=c.id AND d.field='phone'
                       WHERE d.value LIKE '%".$m->escape($dq, 'like')."%' {$only_users_sql}
                       LIMIT {LIMIT}";
        }

        // Name contains requested string
        $sqls[] = "SELECT c.id, c.name, c.login, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                   WHERE c.name LIKE '_%".$m->escape($q, 'like')."%' {$only_users_sql}
                   LIMIT {LIMIT}";

        // Login contains requested string
        $sqls[] = "SELECT c.id, c.name, c.login, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                   WHERE c.login LIKE '_%".$m->escape($q, 'like')."%' {$only_users_sql}
                   LIMIT {LIMIT}";

        // Email contains requested string
        $sqls[] = "SELECT c.id, c.name, c.login, e.email, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                       JOIN wa_contact_emails AS e
                           ON e.contact_id=c.id
                   WHERE e.email LIKE '_%".$m->escape($q, 'like')."%' {$only_users_sql}
                   LIMIT {LIMIT}";

        $limit = $limit !== null ? $limit : self::$limit;
        $result = array();
        $term_safe = htmlspecialchars($q);
        foreach ($sqls as $sql) {
            if (count($result) >= $limit) {
                break;
            }
            foreach ($m->query(str_replace('{LIMIT}', $limit, $sql)) as $c) {
                if (empty($result[$c['id']])) {
                    if (!empty($c['firstname']) || !empty($c['middlename']) || !empty($c['lastname'])) {
                        $c['name'] = waContactNameField::formatName($c);
                    }
                    $name = self::prepare($c['name'], $term_safe);
                    $email = self::prepare(ifset($c['email'], ''), $term_safe);
                    $phone = self::prepare(ifset($c['phone'], ''), $term_safe);
                    $phone && $phone = '<i class="fas fa-phone custom-ml-8 custom-mr-4 text-light-gray"></i>'.$phone;
                    $email && $email = '<i class="fas fa-envelope custom-ml-8 custom-mr-4 text-light-gray"></i>'.$email;
                    $result[$c['id']] = array(
                        'id'        => $c['id'],
//                        'value'     => $c['id'],
                        'name'      => $c['name'],
                        'login'     => $c['login'],
                        'photo_url' => waContact::getPhotoUrl($c['id'], $c['photo'], 96),
                        'label'     => implode(' ', array_filter(array($name, $email, $phone))),
                        'firstname'  => ifset($c['firstname'], ''),
                        'middlename' => ifset($c['middlename'], ''),
                        'lastname'   => ifset($c['lastname'], ''),
                        'email'      => ifset($c['email'], ''),
                        'phone'      => ifset($c['phone'], ''),
                    );
                    if (count($result) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        foreach ($result as &$c) {
            $contact = new waContact($c['id']);
            $c['label'] = "<i class='valign-bottom userpic userpic20 custom-mr-12' style='background-image: url(\"".$contact->getPhoto(20)."\");'></i>".$c['label'];
        }
        unset($c);

        return array_values($result);
    }

    protected static function contactsAutocomplete($q, $limit = null)
    {
        $result = self::usersAutocomplete($q, $limit, false);
        if (!$result) {
            return [];
        }

        $ids = array_column($result, 'id');

        $collection = new waContactsCollection('id/'.join(',', $ids));
        $contacts = $collection->getContacts('id,is_user,phone,email');

        foreach($result as &$c) {
            $id = $c['id'];
            $data = ifset($contacts, $id, []);
            $c['is_user'] = ifset($data, 'is_user', 0);
            if (empty($c['email']) && !empty($data['email'])) {
                $c['email'] = reset($data['email']);
            }
            if (empty($c['phone']) && !empty($data['phone'])) {
                $phone = reset($data['phone']);
                if (!empty($phone['value'])) {
                    $c['phone'] = self::formatNumber($phone['value']);
                }
            }
        }
        unset($c);

        return $result;
    }

    // Helper for contactsAutocomplete()
    protected static function formatNumber($number)
    {
        class_exists('waContactPhoneField');
        $formatter = new waContactPhoneFormatter();
        $number = str_replace(str_split("+-() \n\t"), '', $number);
        return $formatter->format($number);
    }

    // Helper for usersAutocomplete()
    protected static function prepare($str, $term_safe, $escape = true)
    {
        $pattern = '~('.preg_quote($term_safe, '~').')~ui';
        $template = '<span class="bold highlighted">\1</span>';
        if ($escape) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }
        return preg_replace($pattern, $template, $str);
    }
}
