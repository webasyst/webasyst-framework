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
	
	public static function getInfo($currency)
	{
		if (!$currency) {
			return array();
		}
		return include(dirname(__FILE__)."/data/".$currency.".php");
	}
	
	public static function getIntInWords($n, $params = array())
	{
		$delim = isset($params['delim']) ? $params['delim'] : array();
		$offset = isset($params['offset']) ? $params['offset'] : array();
		$order = isset($params['order']) ? $params['order'] : array();
		$plural = isset($params['plural']) ? $params['plural'] : array();
		$i = "";
		$result = "";

		if (!$n) return _ws($n);
		while ($n) {
			$part = (int)substr($n, -3);
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
					$part_result .= _ws($part, $part, (int)$current_plural);
				}
			} else {
				if (isset($order[10]) && !$order[10]) {
					$sub_part = $part % 10;
					$part_result .= ($part_result ? (isset($delim[100]) ? $delim[100] : " ") : "")._ws($sub_part, $sub_part, isset($plural[$sub_part]) ? $plural[$sub_part] : 1);
					if ($part = _ws(substr($part, 0, 1)."0", substr($part, 0, 1)."0", $current_plural)) {
						$part_result .= (isset($delim[10]) ? $delim[10] : " ").$part;
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
	
	public static function format($format, $n, $currency, $locale = null)
	{
		$currency = waCurrency::getInfo($currency);
		waLocale::loadByDomain('webasyst', $locale);
		$locale = waLocale::getInfo($locale);
		return preg_replace('/%(\.?[0-9]?)([w]?)({[n|f|c|s][0-9]?})?/ie', 'self::extract($n, $currency, $locale, "$1", "$2", "$3")', $format);
	}
	
	protected static function extract($n, $currency, $locale, $precision, $format, $desc)
	{	
		$result = '';
		
		$precision = explode('.', $precision);
		if (!isset($precision[1])) {

		} elseif ($precision[0] === '' && $precision[1] === '') {
			$n = (int)$n;
		} elseif ($precision[0] === '') {
			$n = round($n, $precision[1]);
			$n = str_replace(',', '.', $n);
			if (($i = strpos($n, '.')) !== false) {
				$n = substr($n, $i + 1, $precision[1]);
			} else {
				$n = 0;
			}
			while (strlen($n) < 2) {
				$n .= '0';
			}
		}
	
		if ($format == 'w' || $format == 'W') {
			$params = isset($locale['amount_in_words']) ? $locale['amount_in_words'] : array();
			$result = self::getIntInWords($n, $params);
			if ($format == 'W') {
				$result = mb_strtoupper(mb_substr($result, 0, 1)).mb_substr($result, 1);
			}
		} elseif ($precision[0] === '' && !isset($precision[1])) {
			$result = number_format($n, $locale['frac_digits'], $locale['decimal_point'], $locale['thousands_sep']);			
		} else {
			$result = $n;
		}
		
		if (!isset($currency['position'])) {
			$currency['position'] = 0;
		}
		if (!isset($currency['delim'])) {
			$currency['delim'] = ' ';
		}		

		if ($desc) {
			$desc = substr($desc, 1, -1);
			$key = null;			
			if (substr($desc, 0, 1) === 'c') {
				$result .= ' '.$currency['code'];
			} elseif (substr($desc, 0, 1) === 's') {
				switch ($currency['position']) {
					case 1: 
						$result = $currency['symbol'].$currency['delim'].$result;
						break;
					case 0:
					default:
						$result .= $currency['delim'].$currency['symbol'];
				}
			} elseif (substr($desc, 0, 1) === 'f') {
				$key = 'frac_name';
			} elseif (substr($desc, 0, 1) === 'n') {
				$key = 'name';
			} 
			if ($key) {
				$i = (int)substr($desc, 1);
				$str = $currency[$key][$i];
				if (is_array($str)) { 
					$result .= ' '._ws($str[0], $str[1], $n);
				} else {
					$result .= ' '._ws($str);
				}
			}
		}

		return $result;
	}
	
	public function getAll()
	{
		
	}
}