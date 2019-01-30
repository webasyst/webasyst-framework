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

    /**
     * @var resource
     */
    protected $handler;
    protected $settings;
    
    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->handler = $this->connect($settings);
    }
    
    public function reconnect() 
    {
        if ($this->handler) {
            $this->close();
        }
        return $this->handler = $this->connect($this->settings);
    }

    abstract public function connect($settings);

    /**
     * @param $database
     * @return bool
     */
    public function select_db($database)
    {
        return false;
    }

    abstract public function close();

    abstract public function query($query);

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

    public function escape($string)
    {
        return addslashes($string);
    }

    public function escapeField($string)
    {
        return "`".$string."`";
    }

    public function ping()
    {
        return true;
    }


    public function multipleInsert($table, $fields, $values)
    {
        $sql = "INSERT INTO ".$table." (".implode(',', $fields).") VALUES (".implode('), (', $values).")";
        return $this->query($sql);
    }

    abstract public function error();

    abstract public function errorCode();    

    abstract public function insert_id();

    abstract public function affected_rows();

    abstract public function schema($table, $keys = false);
    
    public function getIterator($result)
    {
        return new waDbResultIterator($result, $this);
    }

    public function autocommit($flag) {}

    public function commit() {}

    public function rollback() {}

    public function createSchema($data)
    {
        foreach ($data as $table_id => $table) {
            $this->createTable($table_id, $table);
        }
    }

    public function createTable($table, $data)
    {

    }

    public function createIndex($table, $name, $fields, $unique = false)
    {

    }
}