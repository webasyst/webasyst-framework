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
 * @subpackage currency
 */
class waCurrency
{

    protected static $data;
    protected static $format_data = array();

    /**
     * Returns information about a currency by its ISO3 code, which is retrieved from its config file located in
     *     wa-system/currency/data/.
     *
     * @param string $currency Currency's ISO3 code
     * @return array
     */
    public static function getInfo($currency)
    {
        if (!$currency) {
            return array();
        }
        $data = self::getData();
        return isset($data[$currency]) ? $data[$currency] : array();
    }

    public static function getIntInWords($n, $params = array())
    {
        $n = (int) $n;
        $delim = isset($params['delim']) ? $params['delim'] : array();
        $offset = isset($params['offset']) ? $params['offset'] : array();
        $order = isset($params['order']) ? $params['order'] : array();
        $plural = isset($params['plural']) ? $params['plural'] : array();
        $i = "";
        $result = "";

        if (!$n) {
            return _ws($n);
        }
        while ($n) {
            $part = strlen($n) <= 3 ? (int) $n : (int) substr($n, -3);
            $postfix = $result ? (isset($delim["1".$i]) ? $delim["1".$i] : " ") : "";
            $current_plural = ($i && isset($plural["1".$i])) ? $plural["1".$i] : 1;
            if ($i) {
                $postfix = (isset($delim["1".$i]) ? $delim["1".$i] : " ")._ws("1".$i, "1".$i, $part).$postfix;
            }
            $i .= "000";
            $n = substr($n, 0, -3);

            if (!$n && $part === 1 && isset($params['single']) && $params['single']) {
                return $postfix.$result;
            }
            if (!$part) {
                continue;
            }
            if ($part >= 100) {
                $part_result = _ws(substr($part, 0, 1)."00");
                if ($part_result === substr($part, 0, 1)."00") {
                    $part_result = _ws(substr($part, 0, 1))._ws("100");
                }
                $part = $part % 100;
            } else {
                $part_result = "";
            }
            if ($part <= 20) {
                if ($part) {
                    $part_result .= ($part_result ? (isset($delim[100]) ? $delim[100] : " ") : "");
                    if (isset($offset[$part])) {
                        $part_result = substr($part_result, 0, $offset[$part]);
                    }
                    $part_result .= _ws($part, $part, (int) $current_plural);
                }
            } else {
                if (isset($order[10]) && !$order[10]) {
                    $sub_part = $part % 10;
                    if ($sub_part) {
                        $part_result .= ($part_result ? (isset($delim[100]) ? $delim[100] : " ") : "")._ws($sub_part, $sub_part, isset($plural[$sub_part]) ? $plural[$sub_part] : 1);
                        if ($part = _ws(substr($part, 0, 1)."0", substr($part, 0, 1)."0", $current_plural)) {
                            $part_result .= (isset($delim[10]) ? $delim[10] : " ").$part;
                        }
                    } else {
                        $part_result .= _ws($part, $part, $current_plural);
                    }
                } else {
                    $part_result .= ($part_result ? (isset($delim[100]) ? $delim[100] : " ") : "")._ws(substr($part, 0, 1)."0");
                    if (isset($offset[$part % 10])) {
                        $part_result = substr($part_result, 0, $offset[$part % 10]);
                    }
                    if ($part % 10 && $part = _ws($part % 10, $part % 10, $current_plural)) {
                        $part_result .= (isset($delim[10]) ? $delim[10] : " ").$part;
                    }
                }
            }

            if ($part_result) {
                $result = $part_result.$postfix.$result;
            }

        }
        return $result;
    }

    /**
     * Returns formatted amount value with currency.
     *
     * @see wa_currency()
     * @see wa_currency_html()
     *
     * @param string $format Amount format. The format string must begin with % character and may contain the following
     *     optional parts in the specified order:
     *     - Precision (number of digits after decimal separator) expressed as an arbitrary integer value. If not
     *       specified, then 2 decinal digits are displayed by default.
     *     - Display type (as a number; e.g., "123456", or in words; e.g., 'one hundred and twenty-three thousand four
     *       hundred and fifty-six"). To use the numerical format, specify i; for verbal format, use w. The verbal
     *       expression of a number contains its integer part only, the decimal part is ignored. If the display type is
     *       not specified, then the numerical format is used by default.
     *     - Currency sign or name. To add it to the formatted amount value, specify one of the following identifiers in
     *       curly brackets:
     *         {n}: full cyrrency name; e.g., "dollar"
     *         {s}: brief currency name or sign; e.g., "$"
     *         {f}: name of the fractional currency unit; e.g., "cent/cents"
     *         {c}: currency code; e.g., "USD".
     * @param float $n Original number to be formatted
     * @param string $currency Currency's ISO3 code
     * @param string $locale Locale id
     * @return string E.g., 'en_US'
     */
    public static function format($format, $n, $currency, $locale = null)
    {
        $old_locale = waSystem::getInstance()->getLocale();
        if ($locale === null) {
            $locale = $old_locale;
        }
        if ($locale !== $old_locale) {
            wa()->setLocale($locale);
        }
        $currency = self::getInfo($currency);
        waLocale::loadByDomain('webasyst', $locale);
        $locale = waLocale::getInfo($locale);
        self::$format_data['n'] = $n;
        self::$format_data['locale'] = $locale;
        self::$format_data['currency'] = $currency;
        $pattern = '/%([0-9]?\.?[0-9]?)([iw]*)({[n|f|c|s|h][0-9]?})?/i';
        $result = preg_replace_callback($pattern, array('self', 'replace_callback'), $format);
        if ($locale !== $old_locale) {
            wa()->setLocale($old_locale);
        }
        return $result;
    }

    private static function replace_callback($matches)
    {
        return self::extract(self::$format_data['n'], self::$format_data['currency'], self::$format_data['locale'], $matches[1], $matches[2], ifset($matches[3], ''));
    }

    protected static function extract($n, $currency, $locale, $precision, $format, $desc)
    {
        $result = '';

        // $precision: [0-9]?\.?[0-9]?
        $precision_arr = explode('.', $precision);
        $pad_to_width = false;
        $trim_to_width = false;
        $frac_only_exists = true;
        if ($precision_arr[0] !== '') {
            $precision = (int) $precision_arr[0];
        } elseif (isset($precision_arr[1]) && $precision_arr[1] !== '') {
            $n = ($n - floor($n)) * pow(10, $precision_arr[1]);
            $precision = 0;
            $pad_to_width = $precision_arr[1];
            $trim_to_width = $precision_arr[1];
        } else {
            if ($precision === '.') {
                $frac_only_exists = false;
            }
            $precision = $locale['frac_digits'];
        }

        //
        // $format: [iw]*
        //
        $format_lower = strtolower($format);

        // 'i' format option: floor() $n to $precision.
        // When not present then round() is used.
        // 'w' option implies 'i'
        if (strstr($format_lower, 'i') !== false || strstr($format_lower, 'w') !== false) {
            $n = floor($n * pow(10, $precision)) / ((float) pow(10, $precision));
        } else {
            $n = round($n, $precision);

            // required to show '%.1' correctly for 0.99
            if ($trim_to_width !== false && strlen($n) > $trim_to_width) {
                $n = substr($n, -$trim_to_width);
            }
        }

        // 'w' option: amount written with words
        if (strstr($format_lower, 'w') !== false) {
            // Amount in words.
            // Currently only works for integers.
            $params = isset($locale['amount_in_words']) ? $locale['amount_in_words'] : array();
            $result = self::getIntInWords($n, $params);
            if (strstr($format, 'W') !== false) {
                $result = mb_strtoupper(mb_substr($result, 0, 1)).mb_substr($result, 1);
            }
        } else {
            if ($pad_to_width !== false) {
                $result = str_pad($n, $pad_to_width, '0', STR_PAD_LEFT);
            } else {
                if ($frac_only_exists && ($n == (int) $n) && $precision_arr[0] === '') {
                    $precision = 0;
                }
                $result = number_format($n, $precision, $locale['decimal_point'], $locale['thousands_sep']);
            }
        }

        // by default at the end
        if (!isset($currency['sign_position'])) {
            $currency['sign_position'] = 1;
        }
        if (!isset($currency['sign_delim'])) {
            $currency['sign_delim'] = ' ';
        }


        // $desc: add currency name, or sign, or code, etc.
        if ($desc) {
            $desc = substr($desc, 1, -1);
            $key = null;
            if (substr($desc, 0, 1) === 'c') {
                $result .= ' '.$currency['code'];
            } elseif (substr($desc, 0, 1) === 's' || substr($desc, 0, 1) === 'h') {
                if (substr($desc, 0, 1) === 'h' && !empty($currency['sign_html'])) {
                    $s = $currency['sign_html'];
                }  else {
                    $s = $currency['sign'];
                }
                switch ($currency['sign_position']) {
                    case 0:
                        $result = $s.$currency['sign_delim'].$result;
                        break;
                    case 1:
                    default:
                        $result .= $currency['sign_delim'].$s;
                }
            } elseif (substr($desc, 0, 1) === 'f') {
                $key = 'frac_name';
            } elseif (substr($desc, 0, 1) === 'n') {
                $key = 'name';
            }
            if ($key) {
                $i = (int) substr($desc, 1);
                $str = isset($currency[$key][$i])?$currency[$key][$i]:end($currency[$key]);
                if (is_array($str)) {
                    $result .= ' '._ws($str[0], $str[1], $n);
                } else {
                    $result .= ' '._ws($str);
                }
            }
        }

        return $result;
    }

    protected static function getData()
    {
        if (self::$data === null) {
            $config = wa()->getConfig()->getConfigFile('currency');
            $config_path = wa()->getConfig()->getPath('config').'/currency.php';
            $cache = new waSystemCache('config/currency'.wa()->getLocale());
            if ($config && filemtime($config_path) > $cache->getFilemtime()) {
                self::$data = array();
            } else {
                self::$data = $cache->get();
            }
            if (!self::$data) {
                self::$data = array();
                $files = waFiles::listdir(dirname(__FILE__)."/data/");
                foreach ($files as $file) {
                    if (preg_match('/^([A-Z]{3})\.php$/', $file, $matches)) {
                        $currency = $matches[1];
                        $file = wa()->getConfig()->getPath('system')."/currency/data/".$currency.".php";
                        if (file_exists($file)) {
                            $info = include($file);
                            $info['title'] = _ws($info['title']);
                        } else {
                            $info = array(
                                'sign'  => $currency,
                                'title' => $currency
                            );
                        }
                        self::$data[$currency] = $info;
                    }
                }
                foreach ($config as $cur => $info) {
                    if (!isset(self::$data[$cur])) {
                        self::$data[$cur] = $info;
                    } else {
                        foreach ($info as $k => $v) {
                            self::$data[$cur][$k] = $v;
                        }
                    }
                }
                $cache->set(self::$data);
            }
        }
        return self::$data;
    }

    /**
     * Returns the list of all available currencies.
     *
     * @param string|bool $type Currency data item id specified in currency cofiguration file in wa-system/currency/data/:
     *     - 'all': this value (or true), returns all currency data items
     *     - 'code': currency ISO3 code
     *     - 'sign': currency symbol
     *     - 'title': currency name
     */
    public static function getAll($type = 'title')
    {
        $data = self::getData();
        if ($type === true) {
            $type = 'all';
        }
        switch ($type) {
            case 'code':
            case 'sign':
            case 'title':
                foreach ($data as & $d) {
                    $d = $d[$type];
                }
                return $data;
            default:
                return $data;
        }

    }
}
