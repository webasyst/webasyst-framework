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

    public static function formatName($contact)
    {
        if ($contact['is_company']) {
            return $contact['name'];
        } else {
            $name = array();
            foreach(array('firstname', 'middlename', 'lastname') as $part) {
                if (!isset($contact[$part])) {
                    continue;
                }
                if ( ( $part = trim($contact[$part]))) {
                    $name[] = $part;
                }
            }
            return implode(' ', $name);
        }
    }
}
