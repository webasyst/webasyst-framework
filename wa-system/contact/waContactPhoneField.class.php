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
        $this->options['formats']['value'] = $this->options['formats']['html'] = new waContactPhoneFormatter();
    }

    protected function setValue($value)
    {
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }
        $value = trim((string)$value);
        if ($value) {
            $value = str_replace(str_split('+-()'), '', $value);
            $value = preg_replace('/(\d)\s+(\d)/i', '$1$2', $value);
        }
        return $value;
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        if (isset($params['value'])) {
            $params['value'] = $this->format($params['value'], 'value');
        }
        $params['type']='phone';
        return parent::getHtmlOne($params, $attrs);
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
            '0 800 ##-##-##',
            // 11 digits
            '(0##) ####-####',
            '+1 (###) ###-####',
            '+7 (###) ###-##-##',
            '8 800 ###-####',
            // 12 digits
            '+380 (##) ###-##-##',
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
            return $data;
        } else {
            return parent::format($data);
        }
    }

}
