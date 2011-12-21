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
            $name = $contact['company'];
        } else {
            $name = array();
            foreach(array('firstname', 'middlename', 'lastname') as $part) {
                if ( ($part = trim($contact[$part])) || $part === '0') {
                    $name[] = $part;
                }
            }

            $name = trim(implode(' ', $name));
        }
        if (!$name) {
            $email = $contact->get('email', 'default');
            if (is_array($email)) {
                $email = array_shift($email);
            }
            $name = strtok($email, '@');
            $this->set($contact, $name);
        }

        return $this->format($name, $format);
    }

    public function set(waContact $contact, $value, $params = array(), $add = false)
    {
        $value = trim($value);
        if ($contact['is_company']) {
            return $value;
        }
        $value_parts = explode(' ', trim($value), 3);
        switch (count($value_parts)) {
            case 1:
                $contact['firstname'] = $value;
                break;
            case 2:
                $contact['firstname'] = $value_parts[0];
                $contact['lastname'] = $value_parts[1];
                break;
            case 3:
                $contact['firstname'] = $value_parts[0];
                $contact['middlename'] = $value_parts[1];
                $contact['lastname'] = $value_parts[2];
                break;
        }
        return $value;
    }

    public static function formatName(&$info)
    {
        $name = array();
        foreach(array('firstname', 'middlename', 'lastname') as $part) {
            if (!isset($info[$part])) {
                continue;
            }
            if ( ( $part = trim($info[$part]))) {
                $name[] = $part;
            }
        }
        return implode(' ', $name);
    }
}
