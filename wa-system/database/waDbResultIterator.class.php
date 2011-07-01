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
class waDbResultIterator implements Iterator
{
    /**
     * Current element
     *
     * @var mixid
     */
    protected $current    = null;

    /**
     * Current key
     *
     * @var int
     */
    protected $key        = 0;

    /**
     * Resource
     *
     * @var mysql_result
     */
    private $result;
    private $count = null;
    private $handler;
    /**
     *
     * Enter description here ...
     * @var waDbAdapter
     */
    private $adapter;

    /**
     *
     * @param mysql_result $result
     */
    public function __construct($result, $handler, waDbAdapter $adapter)
    {
        $this->result = $result;
        $this->handler = $handler;
        $this->adapter = $adapter;
    }

    /**
     * Returns current element
     *
     * @return mixed
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Returns current key
     *
     * @return int
     */
    public function key()
    {
        if ($this->key >= $this->count()) {
            $this->rewind();
        }

        return $this->key;
    }

    /**
     * Shift key to the next element
     *
     * @return array
     */
    public function next()
    {
        $this->key++;
        $this->current = $this->adapter->fetch_assoc($this->result);
        return $this->current;
    }

    /**
     * Reset key
     *
     */
    public function rewind()
    {
        $this->current = null;
        $this->key     = 0;
        if ($this->count()) {
            $this->seek(0);
            $this->current  = $this->adapter->fetch_assoc($this->result);
        }
        return $this->current;
    }

    /**
     * Check on valid result
     * Example:
     * <code>
     * while($result->valid()){
     *      print_r($result->next());
     * }
     * </code>
     *
     * @return bool
     */
    public function valid()
    {
        if (!$this->count()) {
            return false;
        }

        return $this->key < $this->count();
    }

    /**
     * Seek on result
     *
     * @return bool
     */
    public function seek($offset = 0)
    {
        if ($offset < $this->count()) {
            return $this->adapter->data_seek($this->result, $offset);
        }

        return false;
    }

    /**
     * Returns result of the count
     *
     * @return int
     */
    public function count()
    {
        if ($this->count === null) {
            $this->count = $this->adapter->num_rows($this->result);
        }
        return $this->count;
    }

    public function fetch($mode = null)
    {
        return $this->adapter->fetch($this->result, $mode);
    }

    public function fetchArray()
    {
        return $this->adapter->fetch_array($this->result);
    }

    public function fetchAssoc()
    {
        return $this->adapter->fetch_assoc($this->result);
    }

    /**
     * Export data
     *
     * @return array
     */
    public function export($key = null, $normalize = false)
    {
        $rows   = array();

        if ($key) {
            foreach ($this as $row) {
                $index = $row[$key];
                if ($normalize) {
                    unset($row[$key]);
                    if (count($row) == 1) {
                        $row = array_shift($row);
                    }
                }
                if ($normalize === 2) {
                    $rows[$index][] = $row;
                } else {
                    $rows[$index] = $row;
                }
            }
        } elseif ($normalize) {
            foreach ($this as $row) {
                $rows[] = array_shift($row);
            }
        } else {
            foreach ($this as $row) {
                $rows[] = $row;
            }
        }
        return (array) $rows;
    }

    /**
     * Free resource
     *
     * @return boolean
     */
    public function free()
    {
        $this->adapter->free($this->result);
    }
}

