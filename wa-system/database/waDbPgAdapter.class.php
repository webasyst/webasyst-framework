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
class waDbPgAdapter extends waDbAdapter
{
    public function connect($settings)
    {
        $connection = "host=".$settings['host'];
        $connection.= " dbname=".$settings['database'];
        $connection.= " user=".$settings['user'];
        $connection.= " password=".$settings['password'];
        return pg_connect($connection);
    }

    public function query($query)
    {
        return pg_query($this->handler, $query);
    }

    public function close()
    {
        return pg_close($this->handler);
    }

    public function num_rows($result)
    {
        return pg_num_rows($result);
    }

    public function insert_id()
    {
        return pg_last_oid($this->handler);
    }

    public function affected_rows()
    {
        return pg_affected_rows($this->handler);
    }

    public function data_seek($result, $offset)
    {
        return pg_result_seek($result, $offset);
    }

    public function free($result)
    {
        return pg_free_result($result);
    }

    public function fetch_array($result, $mode = self::RESULT_NUM)
    {
        return pg_fetch_array($result);
    }

    public function fetch_assoc($result)
    {
        return pg_fetch_assoc($result);
    }

    public function escape($string)
    {
        return pg_escape_string($this->handler, $string);
    }

    public function error()
    {
        return pg_last_error($this->handler);
    }

    public function errorCode()
    {
    }

    public function escapeField($string)
    {
        return $string;
    }

    public function schema($table)
    {
        $sql = "select * from INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$table."'";
        $res = pg_query($this->handler, $sql);
        $result = array();
        while ($row = pg_fetch_assoc($res)) {
            $type = $row['data_type'];
            if ($type == 'integer') {
                $type = 'int';
            }
            $result[$row['column_name']] = array(
                'type' => $type
            );
        }
        return $result;
    }
}