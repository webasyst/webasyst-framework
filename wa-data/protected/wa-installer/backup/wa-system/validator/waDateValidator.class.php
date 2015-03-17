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
     * @return type
     */
    public function isValid($value)
    {
        parent::isValid($value);
        if (is_array($value)) {
            $error = null;
            $year = null;
            if (!empty($value['year'])) {
                $year = $value['year'];
                if ($year < 1 || $year > date('Y') || !is_numeric($year) || floor($year) != $year) {
                    $error = $this->getMessage('incorrect_date');
                }
            };

            $month = null;
            if (!empty($value['month'])) {
                $month = $value['month'];
                if ($month < 1 || $month > 12) {
                    $error = $this->getMessage('incorrect_date');
                }
            };

            $day = null;
            if (!empty($value['day'])) {
                $day = $value['day'];
                if ($day < 1) {
                    $error = $this->getMessage('incorrect_date');
                }
            };

            if ($day && $month) {
                // Febrary
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
            $time = strtotime($value);
            if (!$time) {
                $this->setError($this->getMessage('incorrect_date'));
            } else {
                return $this->isValid(
                    array(
                        "year" => date("Y", $time),
                        "month" => date("m", $time),
                        "day" => date("d", $time)
                    )
                );
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