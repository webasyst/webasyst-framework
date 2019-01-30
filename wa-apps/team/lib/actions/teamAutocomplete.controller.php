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

    public static function usersAutocomplete($q, $limit = null)
    {
        $m = new waModel();

        // The plan is: try queries one by one (starting with fast ones),
        // until we find 5 rows total.
        $sqls = array();

        // Name starts with requested string
        $sqls[] = "SELECT c.id, c.name, c.login, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                   WHERE c.name LIKE '".$m->escape($q, 'like')."%' AND (login IS NOT NULL AND c.is_user<>0)
                   LIMIT {LIMIT}";

        // Login starts with requested string
        $sqls[] = "SELECT c.id, c.name, c.login, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                   WHERE c.login LIKE '".$m->escape($q, 'like')."%' AND (login IS NOT NULL AND c.is_user<>0)
                   LIMIT {LIMIT}";

        // Email starts with requested string
        $sqls[] = "SELECT c.id, c.name, c.login, e.email, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                       JOIN wa_contact_emails AS e
                           ON e.contact_id=c.id
                   WHERE e.email LIKE '".$m->escape($q, 'like')."%' AND (login IS NOT NULL AND c.is_user<>0)
                   LIMIT {LIMIT}";

        // Phone contains requested string
        if (preg_match('~^[wp0-9\-\+\#\*\(\)\. ]+$~', $q)) {
            $dq = preg_replace('/[^\d]+/', '', $q);
            $sqls[] = "SELECT c.id, c.name, c.login, d.value as phone, c.firstname, c.middlename, c.lastname, c.photo
                       FROM wa_contact AS c
                           JOIN wa_contact_data AS d
                               ON d.contact_id=c.id AND d.field='phone'
                       WHERE d.value LIKE '%".$m->escape($dq, 'like')."%' AND (login IS NOT NULL AND c.is_user<>0)
                       LIMIT {LIMIT}";
        }

        // Name contains requested string
        $sqls[] = "SELECT c.id, c.name, c.login, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                   WHERE c.name LIKE '_%".$m->escape($q, 'like')."%' AND (login IS NOT NULL AND c.is_user<>0)
                   LIMIT {LIMIT}";

        // Login contains requested string
        $sqls[] = "SELECT c.id, c.name, c.login, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                   WHERE c.login LIKE '_%".$m->escape($q, 'like')."%' AND (login IS NOT NULL AND c.is_user<>0)
                   LIMIT {LIMIT}";

        // Email contains requested string
        $sqls[] = "SELECT c.id, c.name, c.login, e.email, c.firstname, c.middlename, c.lastname, c.photo
                   FROM wa_contact AS c
                       JOIN wa_contact_emails AS e
                           ON e.contact_id=c.id
                   WHERE e.email LIKE '_%".$m->escape($q, 'like')."%' AND (login IS NOT NULL AND c.is_user<>0)
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
                    $phone && $phone = '<i class="icon16 phone"></i>'.$phone;
                    $email && $email = '<i class="icon16 email"></i>'.$email;
                    $result[$c['id']] = array(
                        'id'        => $c['id'],
//                        'value'     => $c['id'],
                        'name'      => $c['name'],
                        'login'     => $c['login'],
                        'photo_url' => waContact::getPhotoUrl($c['id'], $c['photo'], 96),
                        'label'     => implode(' ', array_filter(array($name, $email, $phone))),
                    );
                    if (count($result) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        foreach ($result as &$c) {
            $contact = new waContact($c['id']);
            $c['label'] = "<i class='icon16 userpic20' style='background-image: url(\"".$contact->getPhoto(20)."\");'></i>".$c['label'];
        }
        unset($c);

        return array_values($result);
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
