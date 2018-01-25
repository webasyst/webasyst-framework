<?php
/**
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2017 Webasyst LLC
 * @package wa-system
 * @subpackage util
 */

class waString
{
    /**
     * Wrapper to simplify htmlspecialchars() usage with proper encoding
     *
     * @param string $str
     * @param int $flags defaults to ENT_COMPAT | ENT_HTML401
     * @return string
     */
    public static function escape($str, $flags = null)
    {
        if ($flags === null) {
            $flags = ENT_COMPAT | ENT_HTML401;
        }
        return htmlspecialchars($str, $flags, 'UTF-8');
    }

    /**
     * Wrapper to simplify htmlentities() usage with proper encoding
     *
     * @param string $str
     * @param int $flags defaults to ENT_COMPAT | ENT_HTML401
     * @return string
     */
    public static function escapeAll($str, $flags = null)
    {
        if ($flags === null) {
            $flags = ENT_COMPAT | ENT_HTML401;
        }
        return htmlentities($str, $flags, 'UTF-8');
    }

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
}
