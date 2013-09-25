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
        if ($this->isMulti()) {
            foreach ($value as $k => $v) {
                if (!isset($v['status'])) {
                    $value[$k]['status'] = $status;
                }
            }
        } else {
            if (!isset($v['status'])) {
                $value['status'] = $status;
            }
        }
        return $value;
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