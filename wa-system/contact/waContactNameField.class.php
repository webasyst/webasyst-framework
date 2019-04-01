<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage contact
 */
class waContactNameField extends waContactStringField
{

    public function init()
    {
        if (!isset($this->options['validators'])) {
            $options = $this->options;
            $options['required'] = true;
            $this->options['validators'] = new waStringValidator($options, array(
                'required' => _ws('At least one of these fields must be filled')
            ));
        }
    }

    public function get(waContact $contact, $format = null)
    {
        if ($contact['is_company']) {
            $name = !empty($contact['company']) ? $contact['company'] : '';
        } else if ($contact['is_user'] && ($contact['is_user'] == 1 || $contact['login'])) {
            $name = waUser::formatName($contact);
        } else {
            $name = array();
            foreach(self::getNameOrder() as $part) {
                if ( ($part = trim($contact[$part])) || $part === '0') {
                    $name[] = $part;
                }
            }

            $name = trim(implode(' ', $name));
        }
        if (!$name && $name !== '0') {
            $email = $contact->get('email', 'default');
            $name = strtok($email, '@');
        }

        return $this->format($name, $format);
    }

    public function prepareSave($value, waContact $contact = null) {

        if (!$contact) {
            return $value;
        }
        if ($contact['is_company']) {
            $name = $contact['company'];
        } else {
            $fst = trim($contact['firstname']);
            $mdl = trim($contact['middlename']);
            $lst = trim($contact['lastname']);
            $cmp = trim($contact['company']);
            $eml = trim($contact->get('email', 'default'));

            $name = array();
            if ($fst || $fst === '0' || $mdl || $mdl === '0' || $lst || $lst === '0')
            {
                $name[] = $lst;
                $name[] = $fst;
                $name[] = $mdl;
            }
            else if ($cmp || $cmp === '0')
            {
                $name[] = $cmp;
            }
            else if ($eml)
            {
                $pos = strpos($eml, '@');
                if ($pos == false) {
                    $name[] = $eml;
                } else {
                    $name[] = substr($eml, 0, $pos);
                }
            }
            foreach ($name as $i => $n) {
                if (!$n && $n !== '0') {
                    unset($name[$i]);
                }
            }
            $name = trim(implode(' ', $name));
        }
        if (!$name && $name !== '0') {
            $name = $contact->getId() ? $contact->getId() : '';
        }
        return $name;
    }

    public function set(waContact $contact, $value, $params = array(), $add = false)
    {
        $value = preg_replace('~\s+~u', ' ', trim($value));

        if ($contact['name'] == $value) {
            return $contact['name'];
        }

        if ($contact['is_company']) {
            $contact['company'] = $value;
            return $value;
        }

        $contact['firstname'] = '';
        $contact['middlename'] = '';
        $contact['lastname'] = '';
        if (!strlen($value)) {
            return $value;
        }

        $value_parts = explode(' ', $value);
        $sf_order = self::getNameOrder();

        switch (count($value_parts)) {
            case 1:
                $contact['firstname'] = $value;
                break;
            case 2:
                if ($sf_order[0] == 'lastname') {
                    $contact['lastname'] = $value_parts[0];
                    $contact['firstname'] = $value_parts[1];
                } else {
                    $contact['firstname'] = $value_parts[0];
                    $contact['lastname'] = $value_parts[1];
                }
                break;
            default:
                while($value_parts && count($sf_order) > 1) {
                    if ($sf_order[0] == 'lastname') {
                        $contact[array_pop($sf_order)] = array_pop($value_parts);
                    } else {
                        $contact[array_shift($sf_order)] = array_shift($value_parts);
                    }
                }
                $contact['lastname'] = join(' ', $value_parts);
                break;
        }
        return $value;
    }

    public static function formatName($contact, $force_not_user = false)
    {
        if (!is_array($contact) && !($contact instanceof waContact)) {
            return '';
        }
        if (!empty($contact['is_company'])) {
            $name = !empty($contact['company']) ? $contact['company'] : '';
        } else if (!$force_not_user && !empty($contact['is_user']) && ($contact['is_user'] == 1 || !empty($contact['login']))) {
            $name = waUser::formatName($contact);
        } else {
            $name = array();
            foreach(self::getNameOrder() as $part) {
                if (!empty($contact[$part])) {
                    if ( ($part = trim($contact[$part])) || $part === '0') {
                        $name[] = $part;
                    }
                }
            }
            $name = trim(implode(' ', $name));
        }

        if (!$name && $name !== '0') {
            $email = '';
            if ($contact instanceof waContact) {
                $email = $contact->get('email', 'default');
            } else if (!empty($contact['email'])) {
                $email = $contact['email'];
                if (is_array($email)) {
                    if (isset($email['value'])) {
                        $email = $email['value'];
                    } else {
                        $email = array_shift($email);
                        if (is_array($email) && isset($email['value'])) {
                            $email = $email['value'];
                        } else if (!is_string($email)) {
                            $email = '';
                        }
                    }
                } else if (!is_string($email)) {
                    $email = '';
                }
            }
            $name = strtok($email, '@');
        }

        if (!$name && !empty($contact['name'])) {
            $name = $contact['name'];
        }
        return $name;
    }

    public static function getNameOrder($clear_cache = false)
    {
        static $result = null;
        if ($clear_cache) {
            $result = null;
        }
        if ($result === null) {
            try {
                $fld = waContactFields::get('name', 'person');
                if ($fld) {
                    $result = $fld->getParameter('subfields_order');
                }
            } catch(Exception $e) {
            }
            $def = array('firstname' => 1, 'middlename' => 1, 'lastname' => 1);
            if ($result && is_array($result)) {
                $result = array_keys(array_intersect_key(array_flip($result) + $def, $def));
            } else {
                $result = array_keys($def);
            }
        }
        return $result;
    }
}

