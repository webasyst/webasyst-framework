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


class waDbIbaseAdapter extends waDbAdapter
{
    public function connect($settings)
    {
        return  ibase_connect($settings['host'].":".$settings['database'], $settings['user'], $settings['password'], $settings['charset']);
    }

    public function query($query)
    {
        return ibase_query($this->handler, $query);
    }

    public function free($result)
    {
        return ibase_free_result($result);
    }

    public function fetch_assoc($result)
    {
        return ibase_fetch_assoc($result);
    }

    public function fetch_array($result, $mode = self::RESULT_NUM)
    {
        return ibase_fetch_row($result, $mode);
    }

    public function close()
    {
        return ibase_close($this->handler);
    }

    public function data_seek($result, $offset) {
    }

    public function num_rows($result)
    {
    }

    public function error()
    {
        return ibase_errmsg($this->handler);
    }

    public function errorCode()
    {
        return ibase_errcode($this->handler);
    }

    public function insert_id()
    {
    }

    public function affected_rows()
    {
            return ibase_affected_rows($this->handler);
    }

    public function schema($table, $keys = false)
    {
    }

    public function getIterator($result)
    {
        return new waDbResultIbaseIterator($result, $this);
    }

}

class waDbResultIbaseIterator extends waDbResultIterator
{

     private $records = array(); // buffer for storing fetched records
     private $eof = true;
     private $result;            // result handler for completed query

    public function __construct($result, waDbAdapter $adapter)
    {
    parent::__construct($result, $adapter);
        $this->result = $result;
        $this->key = -1;
    }

    public function current() // override
    {
        if ($this->key == -1) {
            $this->next();
        }
        return $this->records[$this->key];
    }

    public function rewind()  // override
    {
        $this->key = -1;
        $this->next(); // reading first record
    }

    public function valid()   // override
    {
        return !$this->eof;
    }

    public function key() // override
    {
    return $this->key;
    }

    public function next() // override
    {
        if (count($this->records)-1 == $this->key) // if last record in buffer
        {
            $row = $this->next_fetch();            // trying to fetch next record from query result
        }
        else
        {
            $row = $this->next_record();           // getting next record from buffer
        }
        $this->eof = $row ? false : true;          // setting eof, if there are no records either in query result or in buffer
        return $row;
    }

    /**
     * Returns result of the count
     *
     * @return int
    */
    public function count()  // override
    {
        while (!$this->eof) {
            $this->next();
        }
        return count($this->records);
    }

    private function next_fetch()
    {
        $row = $this->fetchAssoc();
        if ($row) {
        $this->records[] = $row;
            $this->key = count($this->records)-1;
        }
        return $row;
    }

    private function next_record()
    {
    $this->key++;
        return $this->records[$this->key];
    }

}