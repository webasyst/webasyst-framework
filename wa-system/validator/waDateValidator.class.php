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
 * @subpackage validator
 */
class waDateValidator extends waValidator
{
    protected function init()
    {
        parent::init();
        $this->setMessage('incorrect_date', _ws("Incorrect date"));
    }

    /**
     *
     * @param array|string $value
     * @param bool $more_current deprecated
     * @return bool
     */
    public function isValid($value, $more_current = true)
    {
        $value = is_scalar($value) ? trim($value) : $value;
        if (empty($value)) {
            if (!parent::isValid($value)) {
                return false;
            }
            $value = array_fill_keys(['year', 'month', 'day'], null);
        }
        parent::isValid($value);
        if (is_array($value)) {
            $error = null;
            $year = null;
            if (isset($value['year'])) {
                $year = $value['year'];
                if ($year < 1 || !is_numeric($year) || floor($year) != $year) {
                    $error = $this->getMessage('incorrect_date');
                }
            }

            $month = null;
            if (isset($value['month'])) {
                $month = $value['month'];
                if ($month < 1 || $month > 12 || !is_numeric($month)) {
                    $error = $this->getMessage('incorrect_date');
                }
            }

            $day = null;
            if (isset($value['day'])) {
                $day = $value['day'];
                if ($day < 1 || $day > 31 || !is_numeric($day)) {
                    $error = $this->getMessage('incorrect_date');
                }
            }

            if ($day && $month) {
                // February
                if ($month == '2') {
                    if (!$year) {
                        $leap = true;
                    } else {
                        $leap = date("L", strtotime("{$year}-01-01"));
                    }
                    if ($leap && $day > 29) {
                        $error = $this->getMessage('incorrect_date');
                    }
                    if (!$leap && $day > 28) {
                        $error = $this->getMessage('incorrect_date');
                    }
                } else if (in_array($month, array('1', '3', '5', '7', '8', '10', '12'))) {
                    if ($day > 31) {
                        $error = $this->getMessage('incorrect_date');
                    }
                } else {
                    if ($day > 30) {
                        $error = $this->getMessage('incorrect_date');
                    }
                }
            }

            if ($error) {
                $this->setError($error);
            }

        } else {
            $time = strtotime($value . ' 00:00:00');
            $date_parse = date_parse($value);
            if (!$time) {
                $this->setError($this->getMessage('incorrect_date'));
            } else {
                $data_1 = [
                    'year'  => date('Y', $time),
                    'month' => date('m', $time),
                    'day'   => date('d', $time)
                ];
                $data_2 = array_intersect_key($date_parse, array_fill_keys(['year', 'month', 'day'], ''));
                if ($data_1['year'] == $data_2['year']) {
                    /** check 29 feb, 31 apr, jun, sep, nov */
                    if (
                        $data_1['day'] != $data_2['day']
                        || $data_1['month'] != $data_2['month']
                    ) {
                        $this->setError($this->getMessage('incorrect_date'));
                        return false;
                    }
                }

                return $this->isValid($data_1, $more_current);
            }
        }
        return $this->getErrors() ? false : true;
    }
    
    public function isEmpty($value) {
        if (is_array($value)) {
            if (empty($value['year']) && empty($value['month']) && empty($value['day'])) {
                return true;
            }
        } else {
            return parent::isEmpty($value);
        }
    }
    
    public function getMessage($name, $variables = array())
    {
        $message = isset($this->messages[$name]) ? $this->messages[$name] : _ws('Invalid');
        foreach ($variables as $k => $v) {
            if (!$this->isEmpty($v)) {
                if (is_array($v)) {
                    $v = implode(', ', $v);
                }
                $message = str_replace('%'.$k.'%', $v, $message);
            }
        }
        return $message;
    }

}
