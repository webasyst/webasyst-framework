<?php
/**
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @copyright 2014 Serge Rodovnichenko
 * @package wa-system
 * @subpackage util
 */

class waString
{
    /**
     * Generate a random UUID (v4)
     * 
     * @see http://www.ietf.org/rfc/rfc4122.txt
     * @return string
     */
    public static function uuid()
    {
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), // 16 bits for "time_mid"
            mt_rand(0, 0xffff), // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000, // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000, // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

        return $uuid;
    }

    /**
     * Converts string to float with very aggressive algorithm:
     *
     * 1. Change all commas to dots (decimal and thousands separators)
     * 2. Drop any symbols except numbers and dots
     * 3. Drop all dots but last (thousands separator aware)
     *
     * 'abc 100.22' => 100.22
     * '$10,300.55' => 10300.55
     * '.55c' => 0.55
     * 'Be careful. 42!' => 0.42
     * 'No Face No Name No Number' => 0
     *
     * e.t.c.
     *
     * @param string $str
     * @return float
     */
    public static function toFloat($str)
    {
        return (float)preg_replace(array('/,/u', '/[^\d^\.]/u', '/\.(?=[^.]*\.)/u'), array('.'), $str);
    }
}
