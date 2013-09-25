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
 * @subpackage datetime
 */
class waDateTime
{

    public static function getTimeZones()
    {
        $cache_file = wa()->getConfig()->getPath('cache', 'config/timezones'.wa()->getLocale());
        if (file_exists($cache_file) && filemtime($cache_file) > filemtime(dirname(__FILE__)."/data/timezones.php")) {
            return include($cache_file);
        } else {
            $data = self::getAllTimeZones();
            $timezones = array();
            foreach ($data as $timezone_id => $info) {
                foreach ($info[1] as &$c) {
                    $c = _ws(str_replace('_', ' ', $c));
                }
                $timezones[$timezone_id] = $info[0].' '.implode(', ', $info[1]);
            }
            waFiles::create($cache_file);
            waUtils::varExportToFile($timezones, $cache_file);
            return $timezones;
        }
    }

    protected static function getAllTimeZones()
    {
        $file = dirname(__FILE__)."/data/timezones.php";
        if (file_exists($file)) {
            return include($file);
        }

        $data = DateTimeZone::listIdentifiers();
        $time_zones = array();
        foreach ($data as $time_zone_id) {
            $t = explode('/', $time_zone_id, 2);
            $date_time = new DateTime('now');
            $date_time->setTimezone(new DateTimeZone($time_zone_id));
            $offset = (float)$date_time->getOffset()/3600;
            if (isset($t[1])) {
                $time_zones[$offset][$t[0]][] = $t[1];
            } else {
                $time_zones[$offset][''][] = $t[0];
            }
        }

        ksort($time_zones);

        $result = array();
        foreach ($time_zones as $offset => $offset_zones) {
            if ($offset >= 10) {
                $offset = '+'.$offset;
            } elseif ($offset >= 0 && $offset < 10) {
                $offset = '+0'.$offset;
            } elseif ($offset < 0 && $offset > -10) {
                $offset = '−0'.abs($offset);
            } elseif ($offset <= -10) {
                $offset = '−'.abs($offset);
            }
            foreach ($offset_zones as $continent => $zones) {
                if (count($zones) <= 5) {
                    $result[($continent ? $continent."/" : '').$zones[0]] = array($offset, $zones);
                } else {
                    $i = 0;
                    $n = count($zones);
                    while ($i < $n) {
                        $tmp = array();
                        for ($j = 0; $j < 5 && $i + $j < $n; $j++) {
                            $z = $zones[$i + $j];
                            if (($k = strpos($z, '/')) !== false) {
                                $z = substr($z, $k + 1);
                            }
                            $tmp[] = $z;
                        }
                        $result[$continent."/".$zones[$i]] = array($offset, $tmp);
                        $i += 5;
                    }
                }
            }
        }
        waUtils::varExportToFile($result, dirname(__FILE__)."/data/timezones.php");
        return $result;
    }

    public static function getDefaultTimeZone()
    {
        return date_default_timezone_get();
    }


    public static function date($format, $time = null, $timezone = null, $locale = null)
    {
        if (is_numeric($time) && strlen($time)!= 8) {
            $time = date('Y-m-d H:i:s', $time);
        }
        $date_time = new DateTime($time);
        if ($timezone) {
            $date_time->setTimezone(new DateTimeZone($timezone));
        }

        // hack to insert month name in lower case
        if (strpos($format, 'f') !== false) {
            $format = str_replace('f', '@F@', $format);
        }

        $result = $date_time->format($format);

        // hack to insert localized month name
        if (strpos($format, 'F') !== false) {
            $month = $date_time->format('F');
            $local = _ws($month, $month, 2);
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

    protected static function convertFormat($format)
    {
        $replace = array(
            "d" => "%d",
            'j' => strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? "%#d" : "%e",
            "m" => "%m",
            "Y" => "%Y",
            "H" => "%H",
            "i" => "%M",
            "s" => "%S",
            "F" => "%B",
        );
        return str_replace(array_keys($replace), array_values($replace), $format);
    }

    public static function format($format, $time = null, $timezone = null, $locale = null)
    {
        if (!$locale) {
            $locale = wa()->getLocale();
        }
        if (!$timezone) {
            $timezone = wa()->getUser()->getTimezone();
        }
        waLocale::loadByDomain("webasyst", $locale);

        if ($format === 'humandatetime') {
            if (preg_match("/^[0-9]+$/", $time)) {
                $time = date("Y-m-d H:i:s", $time);
            }
            $date_time = new DateTime($time);
            $base_date_time = new DateTime(date("Y-m-d H:i:s",strtotime('-1 day')));
            if ($timezone) {
                $date_timezone = new DateTimeZone($timezone);
                $date_time->setTimezone($date_timezone);
                $base_date_time->setTimezone($date_timezone);
            }

            $day = $date_time->format('z');
            if ($base_date_time->format('z') == $day) {
                $result = _ws('Yesterday');
            } else {
                $base_date_time->modify('+1 day');
                if ($base_date_time->format('z') == $day) {
                    $result = _ws('Today');
                } else {
                    $base_date_time->modify('+1 day');
                    if ($base_date_time->format('z') == $day) {
                        $result = _ws('Tomorrow');
                    } else {
                        $result = self::date(self::getFormat('humandate', $locale), $time, $timezone, $locale);
                    }
                }
            }

            return  $result.' '.self::date(self::getFormat('time', $locale), $time, $timezone, $locale);
        }

        return self::date(self::getFormat($format, $locale), $time, $timezone, $locale);
    }

    public static function getFormat($format, $locale = null)
    {
        if (!$locale) {
            $locale = waSystem::getInstance()->getLocale();
        }
        $locale = waLocale::getInfo($locale);
        $date_formats = $locale['date_formats'];

        $default = array(
            'humandate' => 'd f Y',
            'date' => 'Y-m-d',
            'time' => 'H:i',
            'fulltime' => 'H:i:s',
            'datetime' => 'Y-m-d H:i',
            'fulldatetime' => 'Y-m-d H:i:s',
            'timestamp' => 'U',
        );




        if (isset($date_formats[$format])) {
            return $date_formats[$format];
        } elseif (isset($default[$format])) {
            return $default[$format];
        } elseif ( defined($format) && (strpos($format, 'DATE_') === 0) ) {
            return constant($format);
        } elseif (stripos("ymdhisfjnucrzt", $format) !== false) {
            return $format;
        } else {
            trigger_error("waDateTime format '{$format}' undefined",E_USER_NOTICE);
            return "Y-m-d H:i:s";
        }
    }

    public static function getFormatJS($format, $locale = null)
    {
        $format = self::getFormat($format, $locale);
        $pattern = array(

        //day
            'd',        //day of the month
            'j',        //3 letter name of the day
            'l',        //full name of the day
            'z',        //day of the year

        //month
            'F',        //Month name full
            'M',        //Month name short
            'n',        //numeric month no leading zeros
            'm',        //numeric month leading zeros

        //year
            'Y',         //full numeric year
            'y'        //numeric year: 2 digit
        );
        $replace = array(
            'dd','d','DD','o',
            'MM','M','m','mm',
            'yy','y'
            );
            foreach($pattern as &$p) {
                $p = '/'.$p.'/';
            }
            return preg_replace($pattern, $replace, $format);
    }

    public static function parse($format, $string, $timezone = null, $locale = null)
    {
        $f = self::getFormat($format, $locale);
        preg_match_all("![ymdhisfjn]!i", $f, $match);
        $keys = $match[0];

        $pattern = $f;
        $pattern = str_replace(array('d', 'm', 'y', 'H', 'i', 's'), "([0-9]{1,2})", $pattern);
        $pattern = str_replace(array('j'), " ?([0-9]{1,2})", $pattern);
        $pattern = str_replace(array('Y'), "([0-9]{4})", $pattern);
        $pattern = str_replace(array('F'), "([^\s0-9.,]+)", $pattern);
        $pattern = str_replace(array(' ', '.'), array("\s", '\.'), $pattern);

        if (!preg_match_all("!".$pattern."!uis", $string, $match, PREG_SET_ORDER)) {
            return false;
        }

        $values = $match[0];
        array_shift($values);

        $info = array();
        foreach ($keys as $i => $k) {
            $info[$k] = $values[$i];
        }

        if (!isset($info['s'])) {
            $info['s'] = '00';
        }
        if (!isset($info['m'])) {
            if (isset($info['F'])) {
                if (function_exists('strptime')) {
                    $i = strptime("1 ".$info['F']." ".$info['Y'], "%d %B %Y");
                    $info['m'] = str_pad($i['tm_mon'] + 1, 2, "0", STR_PAD_LEFT);
                } else {
                    $info['m'] = date("m", strtotime("1 ".$info['F']." ".$info['Y']));
                }
            }
        }

        if (!isset($info['d'])) {
            if (isset($info['j'])) {
                $info['d'] = str_pad($info['j'], 2, "0", STR_PAD_LEFT);
            }
        }
        if ($format == 'date' || $format == 'humandate') {
            $result = $info['Y']."-".$info['m']."-".$info['d'];
            $result_format = "Y-m-d";
        } elseif ($format == 'time' || $format == 'fulltime') {
            $result = $info['H'].":".$info['i'].":".$info['s'];
            $result_format = "H-i-s";
        } else {
            $result = $info['Y']."-".$info['m']."-".$info['d']." ".$info['H'].":".$info['i'].":".$info['s'];
            $result_format = "Y-m-d H:i:s";
        }

        if ($timezone === null) {
            $timezone = wa()->getUser()->getTimezone();
        }
        if ($timezone && $timezone != self::getDefaultTimeZone() && $format != 'date') {
            $date_time = new DateTime($result, new DateTimeZone($timezone));
            $date_time->setTimezone(new DateTimeZone(self::getDefaultTimeZone()));
            $result = $date_time->format($result_format);
        }
        return $result;
    }
}
