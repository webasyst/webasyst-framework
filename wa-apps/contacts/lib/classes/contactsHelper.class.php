<?php

class contactsHelper {
    
    public static function getAppPath($path) {
        return wa()->getAppPath($path, wa()->getApp());
    }
    
    public static function getTop($contact)
    {
        $top = array();
        static $fields = null;
        if ($fields === null) {
            $fields = array(
                waContactFields::getAll('person', true),
                waContactFields::getAll('company', true),
            );
        }
        
        $country_model = new waCountryModel();
        $iso3letters_map = $country_model->select('DISTINCT iso3letter')->fetchAll('iso3letter', true);
        
        foreach ($fields[intval($contact['is_company'])] as $f) {
            $info = $f->getInfo();
            if ($f->getParameter('top') && ($value = $contact->get($info['id'], 'top,html')) ) {
                
                if ($info['type'] == 'Address') {
                    $data = $contact->get($info['id']);
                    $data_for_map = $contact->get($info['id'], 'forMap');
                    $value = (array) $value;
                    foreach ($value as $k => &$v) {
                        if (isset($data[$k]['data']['country']) && isset($iso3letters_map[$data[$k]['data']['country']])) {
                            $v = '<img src="'.wa_url().'wa-content/img/country/'.strtolower($data[$k]['data']['country']).'.gif" /> ' . $v;
                        }
                        $map_url = '';
                        if (is_string($data_for_map[$k])) {
                            $map_url = $data_for_map[$k];
                        } else {
                            if (!empty($data_for_map[$k]['coords'])) {
                                $map_url = $data_for_map[$k]['coords'];
                            } else if (!empty($data_for_map[$k]['with_street'])) {
                                $map_url = $data_for_map[$k]['with_street'];
                            }
                        }
                        $v .= ' <a target="_blank" href="//maps.google.com/maps?q=' . urlencode($map_url) . '&z=15" class="small underline map-link">' . _w('map') . '</a>';
                        $v = '<div data-subfield-index='.$k.'>'.$v.'</div>';
                    }
                    unset($v);
                }
                
                $delimiter = ($info['type'] == 'Composite' || $info['type'] == 'Address') ? "<br>" : ", ";
                
                $top[] = array(
                    'id' => $info['id'],
                    'name' => $f->getName(),
                    'value' => is_array($value) ? implode($delimiter, $value) : $value,
                    'icon' => ($info['type'] == 'Email' || $info['type'] == 'Phone') ? strtolower($info['type']) : '',
                    'pic' => ''
                );
            }
        }
        return $top;
    }

    /**
     * @param waContact $user
     * @return string
     */
    public static function getAccessDisableMsg($user)
    {
        $access_disable = '';
        if ($user['is_user'] == '-1') {
            $log_model = new waLogModel();
            $log_item = $log_model->select('*')->where(
                    "subject_contact_id = i:id AND action = 'access_disable'", 
                    array('id' => $user['id'])
                )->order('datetime DESC')->limit(1)->fetch();
            if ($log_item) {
                $contact = new waContact($log_item['contact_id']);
                $name = htmlspecialchars(waContactNameField::formatName($contact));
                $access_disable = _w("Access disabled by") . " <a href='#/contact/{$log_item['contact_id']}/'>{$name}</a>, ".wa_date("humandatetime", $log_item['datetime']);
            }
        }
        return $access_disable;
    }
    
    public static function getFieldsDescription($field_ids, $skip = false)
    {
        $fields = array();
        $all_fields = waContactFields::getAll('enabled');
        if ($skip) {
            foreach ($field_ids as $field_id) {
                if (isset($all_fields[$field_id])) {
                    unset($all_fields[$field_id]);
                }
            }
            $field_ids = array_keys($all_fields);
        }
        foreach ($field_ids as $field_id) {
            $f = $all_fields[$field_id];
            if (!$f) {
                continue;
            }
            /**
             * @var $f waContactField
             */
            $fields[$field_id] = array();
            $fields[$field_id]['id'] = $field_id;
            $fields[$field_id]['name'] = $f->getName();
            $fields[$field_id]['type'] = $f->getType();
            if ($fields[$field_id]['type'] === 'Select') {
                $fields[$field_id]['options'] = $f->getOptions();
            }
            $fields[$field_id]['fields'] = $f instanceof waContactCompositeField;
            if ( ( $ext = $f->getParameter('ext'))) {
                $fields[$field_id]['ext'] = $ext;
                foreach ($fields[$field_id]['ext'] as &$v) {
                    $v = _ws($v);
                }
            }
            $fields[$field_id]['icon'] = ($fields[$field_id]['type'] == 'Email' || $fields[$field_id]['type'] == 'Phone') ? strtolower($fields[$field_id]['type']) : '';
        }
        return $fields;
    }
    
    public static function normalzieContactFieldValues(&$values, $fields) {
        foreach ($fields as $field_info) {
            if (is_object($field_info) && $field_info instanceof waContactField) {
                $field_info = $field_info->getInfo();
            }
            if ($field_info['multi'] && isset($values[$field_info['id']])) {
                $values[$field_info['id']] = array_values($values[$field_info['id']]);
            }
        }
    }
    
    public static function getSearchForm()
    {
        $form = wa('contacts')->event(array('contacts', 'search_form'));
        if (!empty($form)) {
            return reset($form);
        }
        return '';
    }
    
    public static function getAccessTabTitle(waContact $contact)
    {
        $rm = new waContactRightsModel();
        $ugm = new waUserGroupsModel();
        $gm = new waGroupModel();

        // Personal and group access rights
        $groups = $ugm->getGroups($contact['id']);
        $ownAccess = $rm->getApps(-$contact['id'], 'backend', false, false);
        $groupAccess = $rm->getApps(array_keys($groups), 'backend', false, false);
        if(!isset($ownAccess['webasyst'])) {
            $ownAccess['webasyst'] = 0;
        }
        if(!isset($groupAccess['webasyst'])) {
            $groupAccess['webasyst'] = 0;
        }
        
        // Build application list with personal and group access rights for each app
        $apps = wa()->getApps();
        $noAccess = true;
        $gNoAccess = true;
        foreach($apps as $app_id => &$app) {
            $app['id'] = $app_id;
            $app['customizable'] = isset($app['rights']) ? (boolean) $app['rights'] : false;
            $app['access'] = $ownAccess['webasyst'] ? 2 : 0;
            if (!$app['access'] && isset($ownAccess[$app_id])) {
                $app['access'] = $ownAccess[$app_id];
            }
            $app['gaccess'] = $groupAccess['webasyst'] ? 2 : 0;
            if (!$app['gaccess'] && isset($groupAccess[$app_id])) {
                $app['gaccess'] = $groupAccess[$app_id];
            }
            $noAccess = $noAccess && !$app['gaccess'] && !$app['access'];
            $gNoAccess = $gNoAccess && !$app['gaccess'];
        }
        unset($app);
        
        $html = _w('Access');
        $html .= ' <i class="icon16 c-access-icon ';
        if ($contact['is_user'] == -1) {
            $html .= 'delete';
        } else if (!$groupAccess['webasyst'] && !$ownAccess['webasyst'] && $noAccess) {
            $html .= 'key-bw';
        } else {
            $html .= 'key';
        }
        $html .= '"></i>';
        return $html;
    }
}
