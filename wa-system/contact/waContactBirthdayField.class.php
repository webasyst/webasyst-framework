<?php

class waContactBirthdayField extends waContactField
{

    protected function init()
    {
        if (!isset($this->options['formats'])) {
            $this->options['formats'] = array();
        }
        if (!isset($this->options['formats']['html'])) {
            $this->options['formats']['html'] = new waContactBirthdayLocalFormatter(array(
                'prefix' => $this->options['prefix']
            ));
        }
        if (!isset($this->options['formats']['locale'])) {
            $this->options['formats']['locale'] = $this->options['formats']['html'];
        }
        if (!isset($this->options['formats']['list'])) {
            $this->options['formats']['list'] = $this->options['formats']['html'];
        }

        if (empty($this->options['validators'])) {
            $this->options['validators'] = new waDateValidator($this->options, array('required' => _ws('This field is required')));
        }

        parent::init();
    }

    public function getParts($with_prefix = false)
    {
        $parts = array(
            'year', 'month', 'day'
        );
        if ($with_prefix) {
            $prefix = $this->options['prefix'];
            foreach ($parts as &$p) {
                $p = "{$prefix}_{$p}";
            }
            unset($p);
        }
        return $parts;
    }

    public function getPrefix()
    {
        return $this->options['prefix'];
    }

    public function get(waContact $contact, $format = null) {
        $prefix = $this->options['prefix'];
        $data = array(
            'data' => array(
                'year' => $contact[$prefix.'_year'],
                'month' => $contact[$prefix.'_month'],
                'day' => $contact[$prefix.'_day'],
            ),
        );
        if (isset($this->options['formats']['html']) && $this->options['formats']['html'] instanceof waContactFieldFormatter) {
            $data['value'] = $this->options['formats']['html']->format($data);
        }
        return $this->format($data, $format);
    }

    public function set(waContact $contact, $value, $params = array(), $add = false)
    {
        if (is_array($value) && !isset($value['value'])) {
            /*
             * This code allows to assign
             *    $contact['birthday'] = array(
             *        'year' => ...
             *        'month' => ...
             *        'day' => ...
             *    );
             * whereas without it an extra 'value' => array(...) level would be required.
             */
            $value = array(
                'value' => $value,
            );
        } else if (is_string($value)) {
            $value = array(
                'value' => self::parse($value),
            );
        }

        // This allows to read $contact['birthday'] right after assignment
        if (!empty($value['value'])) {
            $prefix = $this->options['prefix'];
            foreach(array('year', 'month', 'day') as $part) {
                if (!empty($value['value'][$part])) {
                    $contact[$prefix.'_'.$part] = $value['value'][$part];
                } else {
                    $contact[$prefix.'_'.$part] = null;
                }
            }
        }

        return $value;
    }

    public function prepareSave($value, waContact $contact = null)
    {
        $prefix = $this->options['prefix'];
        if (is_string($value)) {
            $value = array(
                'value' => self::parse($value)
            );
        }
        if (is_array($value) && isset($value['value'])) {
            foreach ($value['value'] as $name => $v) {
                if (strstr($name, $prefix) === false) {
                    unset($value['value'][$name]);
                    $value['value'][$prefix.'_'.$name] = $v;
                }
            }
        }
        if (empty($value['value'][$prefix.'_year'])) {
            $value['value'][$prefix.'_year'] = null;
        }
        if (empty($value['value'][$prefix.'_month'])) {
            $value['value'][$prefix.'_month'] = null;
        }
        if (empty($value['value'][$prefix.'_day'])) {
            $value['value'][$prefix.'_day'] = null;
        }
        return $value;
    }

    public static function parse($value)
    {
        $d = null;
        $m = null;
        $y = null;
        if (strpos($value, '.') !== false) {
            $value = explode('.', $value, 3);
            if (count($value) >= 2) {
                $d = intval($value[0]);
                $m = intval($value[1]);
                if (count($value) > 2) {
                    $y = intval($value[2]);
                }
            }
        } else if (strpos($value, '/') !== false) {
            $value = explode('/', $value, 3);
            if (count($value) >= 2) {
                $m = intval($value[0]);
                $d = intval($value[1]);
                if (count($value) > 2) {
                    $y = intval($value[2]);
                }
            }
        } else {
            $value = strtotime($value);
            $y = date('Y', $value);
            $m = date('n', $value);
            $d = date('j', $value);
        }
        return array(
            'year' => $y,
            'month' => $m,
            'day' => $d
        );
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        $value = isset($params['value']['data']) ? $params['value']['data'] : '';
        if (!is_array($value)) {
            $value = isset($params['value']['value']) ? $params['value']['value'] : '';
        }
        $ext = null;

        $name_input = $name = $this->getHTMLName($params);

        $disabled = '';
        if (wa()->getEnv() === 'frontend' && isset($params['my_profile']) && $params['my_profile'] == '1') {
            $disabled = 'disabled="disabled"';
        }

        $result = "";

        $result .= '<select '.$attrs.' '.$disabled.' name="'.htmlspecialchars($name_input).'[day]">';
        $selected_day = !empty($value['day']) ? " selected" : "";
        $result .= '<option value=""'.$selected_day.'>-</option>';
        for($day = 1; $day <= 31; $day++) {
            $selected_day = (isset($value['day']) && $day == $value['day']) ? " selected" : "";
            $result .= '<option value="'.$day.'"'.$selected_day.'>'.$day.'</option>';
        }
        $result .= '</select>';

        $months = array(
            1  => _ws('January'),
            2  => _ws('February'),
            3  => _ws('March'),
            4  => _ws('April'),
            5  => _ws('May'),
            6  => _ws('June'),
            7  => _ws('July'),
            8  => _ws('August'),
            9  => _ws('September'),
            10 => _ws('October'),
            11 => _ws('November'),
            12 => _ws('December')
        );
        $result .= '<select '.$attrs.' '.$disabled.' name="'.htmlspecialchars($name_input).'[month]">';
        $selected_month = !empty($value['month']) ? " selected" : "";
        $result .= '<option value=""'.$selected_month.'>-</option>';
        foreach($months as $month_id => $month) {
            $selected_month = (isset($value['month']) && $month_id == $value['month']) ? " selected" : "";
            $result .= '<option value="'.$month_id.'"'.$selected_month.'>'.$month.'</option>';
        }
        $result .= '</select>';

        $result .= '<input '.$attrs.' '.$disabled.' type="text" name="'.htmlspecialchars($name_input).'[year]" value="'.htmlspecialchars(!empty($value['year'])?$value['year']:"").'" style="width: 4em; min-width: 4em;">';

        return $result;
    }
}

class waContactBirthdayLocalFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        $date = array();
        if ($data['data']['year']) {
            $value = $data['data']['year'];
            $date["y"] = $data['data']['year'];
        } else {
            // use leap year, for correct formating 29 Febrary
            $value = date("Y");
            while (!date("L", strtotime("{$value}-01-01"))) {
                $value += 1;
            }
        }
        if ($data['data']['month']) {
            $value .= "-".($data['data']['month'] < 10 ? "0" : "").$data['data']['month'];
            $date["f"] = $data['data']['month'];
        } else {
            $value .= "-01";
        }
        if ($data['data']['day']) {
            $value .= "-".($data['data']['day'] < 10 ? "0" : "").$data['data']['day'];
            $date["j"] = $data['data']['day'];
        } else {
            $value .= "-01";
        }

        $format = array();
        foreach (explode(" ", waDateTime::getFormat('humandate')) as $p) {
            $f = strtolower(substr($p, 0, 1));
            if (isset($date[$f])) {
                $format[] = $p;
            }
        }
        $format = implode(" ", $format);
        $format = preg_replace("/[^yfj]$/i", "", $format);

        $date_time = new DateTime($value);

        // hack to insert month name in lower case
        if (strpos($format, 'f') !== false) {
            $format = str_replace('f', '@F@', $format);
        }

        $result = $date_time->format($format);

        // hack to insert localized month name
        if (strpos($format, 'F') !== false) {
            $month = $date_time->format('F');
            $local = _ws($month, $month, strpos($format, 'j') !== false ? 2 : 1);
            $result = str_replace(
                array(
                    "@$month@",
                    $month
                ),
                array(
                    mb_strtolower($local),
                    $local
                ),
                $result
            );
        }
        return $result;
    }
}

class waContactBirthdayJSFormatter extends waContactFieldFormatter {
    public function format($data) {
        return $data;
    }
}