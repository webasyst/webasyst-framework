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

    /**
     * Returns the list of available time zones with localized descriptions.
     *
     * @return array
     */
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

    public static function getAllTimeZones()
    {
        $file = dirname(__FILE__)."/data/timezones.php";
        if (file_exists($file)) {
            $data = include($file);
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                $tmp = array();
                foreach ($data as $k => $v) {
                    $date_time = new DateTime('now');
                    $tz = new DateTimeZone($k);
                    $date_time->setTimezone($tz);
                    $offset = $date_time->getOffset();
                    if ($offset !== false) {
                        $data[$k][0] = ($offset >= 0 ? '+' : '−').str_pad((float)abs($offset)/3600, 2, '0', STR_PAD_LEFT);
                    }
                    $tmp[$k] = $offset;
                }
                asort($tmp);
                foreach ($tmp as $k => $offset) {
                    $tmp[$k] = $data[$k];
                }
                $data = $tmp;
            }
            return $data;
        }

        $data = DateTimeZone::listIdentifiers();
        $time_zones = array();
        foreach ($data as $time_zone_id) {
            $t = explode('/', $time_zone_id, 2);
            $date_time = new DateTime('now');
            $tz = new DateTimeZone($time_zone_id);
            $date_time->setTimezone($tz);
            $offset = (float)$date_time->getOffset()/3600;
            $group = count($tz->getTransitions(strtotime('-1 year'), time()));
            if (isset($t[1])) {
                $time_zones[$offset][$group][$t[0]][] = $t[1];
            } else {
                $time_zones[$offset][$group][''][] = $t[0];
            }
        }

        ksort($time_zones);

        $result = array();
        foreach ($time_zones as $offset => $group_offset_zones) {
            foreach ($group_offset_zones as $group => $offset_zones) {
                if ($offset >= 10) {
                    $str_offset = '+' . $offset;
                } elseif ($offset >= 0 && $offset < 10) {
                    $str_offset = '+0' . $offset;
                } elseif ($offset < 0 && $offset > -10) {
                    $str_offset = '−0' . abs($offset);
                } elseif ($offset <= -10) {
                    $str_offset = '−' . abs($offset);
                }
                foreach ($offset_zones as $continent => $zones) {
                    if (count($zones) <= 5) {
                        $result[($continent ? $continent . "/" : '') . $zones[0]] = array($str_offset, $zones);
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
                            $result[$continent . "/" . $zones[$i]] = array($str_offset, $tmp);
                            $i += 5;
                        }
                    }
                }
            }
        }
        waUtils::varExportToFile($result, dirname(__FILE__)."/data/timezones.php");
        return $result;
    }

    /**
     * Returns the default time zone.
     *
     * @return string
     */
    public static function getDefaultTimeZone()
    {
        return date_default_timezone_get();
    }

    /**
     * Returns date as string according to specified format.
     *
     * @param string $format Date format. Format symbols acceptable for PHP function date are supported. To display
     *     month name in lowercase, character 'f' should be used.
     * @param int|string|null $time Unix timestamp. If not specified, current timestamp is used.
     * @param string|null $timezone Time zone identifier. If not specified, the time zone is determined automatically.
     * @param string|null $locale Locale identifier. If not specifed, current user's locale is determined automatically.
     * @return string
     */
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

            $old_locale = waLocale::getLocale();
            if ($locale && $locale != $old_locale) {
                wa()->setLocale($locale);
            }
            $local = _ws($month, $month, 2);
            $result = str_replace(
                array("@$month@", $month),
                array(mb_strtolower($local), $local),
                $result
            );
            if ($locale && $locale != $old_locale) {
                wa()->setLocale($old_locale);
            }
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

    /**
     * Returns time as string according to specified format.
     *
     * @see wa_date()
     *
     * @param string $format Date/time format. The following format strings are acceptable:
     *     - 'humandatetime': adds words "yesterday", "today", "tomorrow" instead of appropriate dates relative to the
     *       current user date
     *     - 'humandate': returns the date in format 'd f Y' supported by method date (format strings listed below are
     *       also supported by that method)
     *     - 'date': returns date/time in format 'Y-m-d'
     *     - 'time': returns date/time in format 'H:i'
     *     - 'fulltime': returns date/time in format 'H:i:s'
     *     - 'datetime': returns date/time in format 'Y-m-d H:i'
     *     - 'fulldatetime': returns date/time in format 'Y-m-d H:i:s'
     *     - 'timestamp': returns date/time in format 'U'
     * @param string|null $time Unix timestamp. If not specified, current timestamp is used.
     * @param string|null $timezone Time zone identifier. If not specified, the time zone is determined automatically.
     * @param string|null $locale Locale identifier. If not specifed, current user's locale is determined automatically.
     * @return string
     */
    public static function format($format, $time = null, $timezone = null, $locale = null)
    {
        if (!$locale) {
            $locale = wa()->getLocale();
        }
        if (!$timezone) {
            $timezone = wa()->getUser()->getTimezone();
        }
        waLocale::loadByDomain("webasyst", $locale);

        $old_locale = waLocale::getLocale();
        if ($locale != $old_locale) {
            wa()->setLocale($locale);
        }

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

            $day = $date_time->format('Y z');
            if ($base_date_time->format('Y z') === $day) {
                $result = _ws('Yesterday');
            } else {
                $base_date_time->modify('+1 day');
                if ($base_date_time->format('Y z') === $day) {
                    $result = _ws('Today');
                } else {
                    $base_date_time->modify('+1 day');
                    if ($base_date_time->format('Y z') === $day) {
                        $result = _ws('Tomorrow');
                    } else {
                        $result = self::date(self::getFormat('humandate', $locale), $time, $timezone, $locale);
                    }
                }
            }

            $result = $result.' '.self::date(self::getFormat('time', $locale), $time, $timezone, $locale);
        } else {
            $result = self::date(self::getFormat($format, $locale), $time, $timezone, $locale);
        }
        if ($locale != $old_locale) {
            wa()->setLocale($old_locale);
        }
        return $result;
    }

    /**
     * Returns format strings for PHP function date corresponding to formats used by Webasyst framework.
     *
     * @param string $format Time format strings used in Webasyst framework including the following options:
     *     - 'date_formats' sub-array keys specified in locale configuration file located in wa-system/locale/data/,
     *     - PHP class DateTime constants,
     *     - format strings acceptable for PHP function date, or one of the identifiers corresponding to pre-defined
     *       time formatting strings supported by method format().
     * @see self::format()
     * @param string|null $locale Locale identifier. If not specifed, current user locale is determined automatically.
     * @return string
     */
    public static function getFormat($format, $locale = null)
    {
        if (!$locale) {
            $locale = waSystem::getInstance()->getLocale();
        }
        $locale = waLocale::getInfo($locale);
        $date_formats = isset($locale['date_formats']) ? $locale['date_formats'] : array();

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

    /**
     * Returns format strings for date/time formatting by means of JavaScript code corresponding to formats used by
     * Webasyst framework.
     *
     * @param string $format Format string accepted by parameter$format of method getFormat().
     * @see self::getFormat()
     * @param string|null $locale Locale identifier. If not specifed, current user's locale is determined automatically.
     * @see string
     */
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

    /**
     * Returns time value, formatted using one of the formats supported by Webasyst framework, as a string accepted by
     * standard PHP functions.
     *
     * @see wa_parse_date()
     *
     * @param string $format Format string accepted by format() method except for 'humandatetime'.
     * @see self::format()
     * @param string $string Date/time value string formatted to match the format identifier specified in $format parameter.
     * @param string|null $timezone Time zone identifier. If not specified, current time zone is determined automatically.
     * @param string|null $locale Locale identifier. If not specifed, current user locale is determined automatically.
     * @return string
     */
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
