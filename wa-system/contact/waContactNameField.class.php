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
                if ( ( $part = trim($contact[$part])) || $part === '0') {
                    $name[] = $part;
                }
            }

            $name = implode(' ', $name);
        }
        $contact[$this->getId()] = $name;
        return $name;
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

// EOF