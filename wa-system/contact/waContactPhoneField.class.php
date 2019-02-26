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
class waContactPhoneField extends waContactStringField
{

    public function init()
    {
        parent::init();
        $this->options['formats']['js'] = new waContactPhoneJsFormatter();
        $this->options['formats']['value'] = new waContactPhoneFormatter();
        $this->options['formats']['html'] = new waContactPhoneTopFormatter();
        $this->options['formats']['top'] = new waContactPhoneTopFormatter();
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = new waPhoneNumberValidator($this->options, array('required' => _ws('This field is required')));
        }
    }

    public static function cleanPhoneNumber($value)
    {
        $value = is_scalar($value) ? (string)$value : '';
        $value = trim($value);
        if (strlen($value) > 0) {
            $value = str_replace(str_split('+-()'), '', $value);
            $value = preg_replace('/(\d)\s+(\d)/i', '$1$2', $value);
        }
        return $value;
    }

    public static function isPhoneEquals($phone1, $phone2)
    {
        if (!is_scalar($phone1) || !is_scalar($phone2)) {
            return false;
        }
        return self::cleanPhoneNumber($phone1) === self::cleanPhoneNumber($phone2);
    }

    protected function setValue($value)
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            $v = $value['value'];
        } else {
            $v = $value;
        }
        $v = trim((string)$v);
        if ($v) {
            $v = str_replace(str_split('+-()'), '', $v);
            $v = preg_replace('/(\d)\s+(\d)/i', '$1$2', $v);
        }
        if (is_array($value) && isset($value['value'])) {
            $value['value'] = $v;
        } else {
            $value = $v;
        }
        return $value;
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        if (isset($params['value'])) {
            if (is_array($params['value']) && isset($params['value']['value'])) {
                $params['value']['value'] = $this->format($params['value']['value'], 'value');
            } else {
                $params['value'] = $this->format($params['value'], 'value');
            }
        }
        return parent::getHtmlOne($params, $attrs);
    }

    public function format($data, $format = null)
    {
        $data = parent::format($data, $format);
        if ($format && !is_array($format)) {
            $format = explode(',', $format);
        }
        if ($format && in_array('html', $format)) {
            if ($this->isMulti()) {
                if (is_array($data)) {
                    $result = htmlspecialchars($data['value']);
                    if (isset($data['ext']) && $data['ext']) {
                        $ext = $data['ext'];
                        if (isset($this->options['ext'][$ext])) {
                            $ext = _ws($this->options['ext'][$ext]);
                        }
                        $result .= ' <em class="hint">'.htmlspecialchars($ext).'</em>';
                    }
                    $data = $result;
                }
            } else {
                if (!is_array($data) || isset($data['value'])) {
                    $data = htmlspecialchars(is_array($data) ? $data['value'] : $data);
                }
            }
        }
        return $data;
    }


    public function validate($data, $contact_id=null)
    {
        $errors = parent::validate($data, $contact_id);
        if ($errors) {
            return $errors;
        }

        if ($this->isMulti()) {
            if (!empty($data[0])  && $contact_id) {
                $value = $this->format($data[0], 'value');
                $phone_exists = $this->phoneExistsAmongAuthorizedContacts($value, $contact_id);
                if ($phone_exists) {
                    $errors[0] = sprintf(_ws('User with the same “%s” field value is already registered.'), _ws('Phone'));
                }
            }
        } else {
            $value = $this->format($data, 'value');
            $phone_exists = $this->phoneExistsAmongAuthorizedContacts($value, $contact_id);
            if ($phone_exists) {
                $errors = sprintf(_ws('User with the same “%s” field value is already registered.'), _ws('Phone'));
            }
        }
        return $errors;
    }

    /**
     * Helper method
     * Checks that suggested phone value for current contact ALREADY exists among all default phones of authorized contacts
     * Authorize contact is contact with password != 0
     * @param string $suggested_phone_value
     * @param id|null $contact_id
     * @return bool
     */
    protected function phoneExistsAmongAuthorizedContacts($suggested_phone_value, $contact_id = null)
    {
        $suggested_phone_value = is_scalar($suggested_phone_value) ? (string)$suggested_phone_value : '';
        if (strlen($suggested_phone_value) <= 0) {
            // phone is empty - not go further - it's doesn't matter
            return false;
        }

        $contact_model = new waContactModel();

        $contact = null;
        if ($contact_id > 0) {
            $contact = $contact_model->getById($contact_id);
            $is_authorized = $contact && $contact['password'];
            if (!$is_authorized) {
                // not authorized contact (or not exists) - not go further - it's doesn't matter
                return false;
            }
        }

        $data_model = new waContactDataModel();

        // update phone for existing contact case - check if value has changed
        if ($contact) {
            $old_value_row = $data_model->getPhone($contact_id);
            if ($old_value_row && waContactPhoneField::isPhoneEquals($suggested_phone_value, $old_value_row['value'])) {
                // phone has not changed - so not go further - it's doesn't matter
                return false;
            }

            $contact_id = $contact['id'];
        }

        // check suggested phone value for current contact ALREADY exists among all default phones of authorized contacts
        $other_contact_id = $data_model->getContactWithPasswordByPhone($suggested_phone_value, $contact_id);
        return $other_contact_id > 0;
    }

}

class waContactPhoneFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        if (is_array($data)) {
            $v = ifset($data['value'], '');
        } else {
            $v = $data;
        }
        if (!$v) {
            return $v;
        }

        $v = explode(' ', $v);
        if (!preg_match('/^[0-9]+$/i', $v[0])) {
            return implode(' ', $v);
        }
        $n = strlen($v[0]);

        $formats_str  = array(
            // 10 digits
            '0 800 ###-###',
            wa()->getLocale() == 'ru_RU' ? '(###) ###-##-##' : '(###) ###-####',
            // 11 digits
            '(0##) ####-####',
            '+1 (###) ###-####',
            '+7 (###) ###-##-##',
            '8 800 ###-####',
            // 12 digits
            '+38 (0##) ###-##-##',
            '+375 (##) ###-##-##',
            '+44 ## ####-####',
        );
        $formats = array();
        foreach ($formats_str as $f) {
            $clean = str_replace(str_split('+-() '), '', $f);
            $formats[strlen($clean)][str_replace('#', '', $clean)] = $f;
        }

        if (isset($formats[$n])) {
            foreach ($formats[$n] as $prefix => $f) {
                if (substr($v[0], 0, strlen($prefix)) == $prefix) {
                    $f = str_split($f);
                    $i = 0;
                    foreach ($f as &$c) {
                        if (is_numeric($c)) {
                            $i++;
                        } elseif ($c === '#') {
                            $c = $v[0][$i++];
                        }
                    }
                    unset($c);
                    $v[0] = implode('', $f);
                    return implode(' ', $v);
                }
            }
        }

        switch ($n) {
            case 12:
                $result = substr($v[0], 0, 2). ' ';
                $result .= substr($v[0], 2, 3).' ';
                $result .= $this->split(substr($v[0], 5), array(3, 2, 2));
                $v[0] = '+'.$result;
                break;
            case 10:
            case 11:
                $result = '';
                $o = 0;
                if ($n == 11) {
                    $result .= substr($v[0], 0, 1).' ';
                    $o = 1;
                }
                $result .= '('.substr($v[0], $o, 3).') ';
                $result .=  $this->split(substr($v[0], $o + 3), array(3, 2, 2));
                $v[0] = ($v[0][0] == '0' || $v[0][0] == '8' ? '' : '+').$result;
                break;
            case 7:
                $v[0] = $this->split($v[0], array(3, 2, 2));
                break;
            case 6:
                $v[0] = $this->split($v[0], array(3, 3));
                break;
        }
        return implode(' ', $v);
    }

    protected function split($str, $ns, $split = '-')
    {
        $result = array();
        $offset = 0;
        foreach ($ns as $n) {
            $result[] = substr($str, $offset, $n);
            $offset += $n;
        }
        return implode('-', $result);
    }
}

class waContactPhoneJsFormatter extends waContactPhoneFormatter
{
    public function format($data)
    {
        if (is_array($data)) {
            $data['value'] = parent::format($data);
            unset($data['status']);

            // No htmlspecialchars, because isn't needed here
            // This formatted data means to be used in js code,
            // make escape there by yourself
            return $data;
        } else {
            return parent::format($data);
        }
    }
}

class waContactPhoneTopFormatter extends waContactPhoneFormatter
{
    public function format($data)
    {
        $result = '';
        if (is_array($data)) {
            $result = parent::format($data);
            $result = htmlspecialchars($result);
            if (!empty($data['ext'])) {
                $result .= " <em class='hint'>" . _ws(htmlspecialchars($data['ext'])) . "</em>";
            }
        } else {
            $result = parent::format($data);
        }
        return $result;
    }
}
