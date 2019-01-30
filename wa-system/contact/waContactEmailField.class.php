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
class waContactEmailField extends waContactStringField
{
    public function init()
    {
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = new waEmailValidator($this->options, array('required' => _ws('This field is required')));
            $this->options['formats']['js'] = new waContactEmailListFormatter();
            $this->options['formats']['html'] = $this->options['formats']['top'] = new waContactEmailTopFormatter();
        }
    }

    public function set(waContact $contact, $value, $params = array(), $add = false)
    {
        $value = parent::set($contact, $value, $params, $add);
        $status = wa()->getEnv() == 'frontend' ? 'unconfirmed' : 'unknown';
        $old = $contact->get($this->id);
        if ($this->isMulti()) {
            foreach ($value as $k => $v) {
                if (!isset($v['status'])) {
                    if ($old && isset($old[$k]['status']) && ($old[$k]['value'] == $v['value'])) {
                        $value[$k]['status'] = $old[$k]['status'];
                    } else {
                        $value[$k]['status'] = $status;
                    }
                }
            }
        } else {
            if (is_array($value) && !isset($value['status'])) {
                if ($old && isset($old['status']) && ($value['value'] == $old['value'])) {
                    $value['status'] = $old['status'];
                } else {
                    $value['status'] = $status;
                }
            }
        }
        return $value;
    }

    public function validate($data, $contact_id=null)
    {
        $errors = parent::validate($data, $contact_id);
        if ($errors) {
            return $errors;
        }

        if ($this->isMulti()) {
            if (!empty($data[0]) && $contact_id) {
                $value = $this->format($data[0], 'value');
                $email_exists = $this->emailExistsAmongAuthorizedContacts($value, $contact_id);
                if ($email_exists) {
                    $errors[0] = sprintf(_ws('User with the same “%s” field value is already registered.'), _ws('Email'));
                }
            }
        } else {
            $value = $this->format($data, 'value');
            $email_exists = $this->emailExistsAmongAuthorizedContacts($value, $contact_id);
            if ($email_exists) {
                $errors = sprintf(_ws('User with the same “%s” field value is already registered.'), _ws('Email'));
            }
        }
        return $errors;
    }


    /**
     * Helper method
     * Checks that suggested email value for current contact ALREADY exists among all default emails of authorized contacts
     * Authorize contact is contact with password != 0
     * @param string $suggested_email_value
     * @param id|null $contact_id
     * @return bool
     */
    protected function emailExistsAmongAuthorizedContacts($suggested_email_value, $contact_id = null)
    {
        $suggested_email_value = is_scalar($suggested_email_value) ? (string)$suggested_email_value : '';
        if (strlen($suggested_email_value) <= 0) {
            // email is empty - not go further - it's doesn't matter
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

        $email_model = new waContactEmailsModel();

        // update email for existing contact case - check if value has changed
        if ($contact) {
            $old_value_row = $email_model->getEmail($contact_id);
            if ($old_value_row && $this->areStringsEqual($suggested_email_value, $old_value_row['email'])) {
                // email has not changed - so not go further - it's doesn't matter
                return false;
            }

            $contact_id = $contact['id'];
        }

        // check suggested email value for current contact ALREADY exists among all default emails of authorized contacts
        $other_contact_id = $email_model->getContactWithPassword($suggested_email_value, $contact_id);
        return $other_contact_id > 0;
    }

    protected function areStringsEqual($email_1, $email_2)
    {
        if (!is_scalar($email_1) || !is_scalar($email_2)) {
            return false;
        }
        return trim((string) $email_1) === trim((string) $email_2);
    }
}

class waContactEmailListFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        if (is_array($data)) {
            $data['data'] = $data['value'];
        } else {
            $data = array(
                'data' => $data,
                'value' => $data
            );
        }
        if (!$data['data']) {
            $data['value'] = '';
            return $data;
        }
        $href = 'mailto:'.$data['data'];
        $data['value'] = '<a href="'.addslashes($href).'">'.htmlspecialchars($data['value']).'</a>';
        return $data;
    }
}

class waContactEmailTopFormatter extends waContactFieldFormatter
{
    public function format($data) {
        if (is_array($data)) {
            $result = htmlspecialchars($data['value']);
            $result = '<a class="inline" href="mailto:'.$result.'">'.$result.'</a>';
            if (isset($data['ext']) && $data['ext']) {
                $ext = $data['ext'];
                $f = waContactFields::get('email');
                $exts = $f->getParameter('ext');
                if (isset($exts[$ext])) {
                    $ext = _ws($exts[$ext]);
                }
                $result .= ' <em class="hint">'.htmlspecialchars($ext).'</em>';
            }
            return $result;
        }
        return htmlspecialchars($data);
    }
}
