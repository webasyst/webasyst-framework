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
 * @subpackage database
 */
abstract class waDbAdapter 
{
    const RESULT_ASSOC = 1;
    const RESULT_NUM = 2;
    const RESULT_BOTH = 3;

    abstract public function connect($settings);

    abstract public function close($handler);

    abstract public function query($query, $handler);

    abstract public function free($result);

    abstract public function data_seek($result, $offset);

    abstract public function num_rows($result);

    public function fetch($result, $mode = self::RESULT_BOTH)
    {
        if ($mode === null) {
            $mode = self::RESULT_BOTH;
        }
        return $this->fetch_array($result, $mode);
    }

    abstract public function fetch_array($result, $mode = self::RESULT_NUM);

    public function fetch_assoc($result)
    {
        return $this->fetch_array($result, $mode = self::RESULT_ASSOC);
    }

    public function escape($string, $handler)
    {
        return addslashes($string);
    }

    public function escapeField($string)
    {
        return "`".$string."`";
    }

    public function ping($handler)
    {
        return true;
    }

    abstract public function error($handler);

    abstract public function errorCode($handler);    

    abstract public function insert_id($handler);

    abstract public function affected_rows($handler);

    abstract public function schema($table, $handler);
}