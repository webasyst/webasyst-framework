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
class waDbCacheIterator implements Iterator
{
    /**
     * Current key
     * 
     * @var int
     */
    protected $key = '';
    
    /**
     * Array of the data
     *
     * @var array
     */
    private $array = array();
    
    private $iteration = 0;

    /**
     * @param array $array
     */
    public function __construct($array)
    {
        $this->array = (array)$array;
    }
    
    /**
     * Return the current element
     * 
     * @return mixed
     */
    public function current()
    {
        return current($this->array);
    }
    
    /**
     *  Return the key of the current element
     * 
     * @return int
     */
    public function key()
    {
        return key($this->array);
    }
    
    /**
     * Move forward to next element
     */
    public function next()
    {
        next($this->array);
        $this->key = $this->key();
        $this->iteration++;
    }
    
    /**
     * Rewind the Iterator to the first element
     *
     * @return mixed
     */
    public function rewind()
    {
        $this->key = 0;
        $this->iteration = 0;
        return reset($this->array);
    }
    
    /**
     * Checks if current position is valid
     * 
     * @return bool
     */
    public function valid()
    {
        if (!$this->count()) {
            return false;
        }
        
        return $this->iteration < $this->count();
    }

    /**
     * Move internal pointer
     *
     * @param int $offset
     * @return bool
     */
    public function seek($offset = 0)
    {
        for ($i = 0; $i < $offset; $i++)
        {
            next($this->array);
        }
        return true;
    }
    
    /**
     * Return number of the elements
     *
     * @return int
     */
    public function count()
    {
        return count($this->array);
    }
    
    public function fetch()
    {
        $row = $this->current();
        next($this->array);
        return $row;
    }

    /**
     * Export data
     *
     * @param string|bool $key
     * @param bool $normalize
     * @return array
     */
    public function export($key = null, $normalize = false)
    {
        if (isset($key)) {
            $rows   = array();
            foreach ($this->array as $row) {
                $index = $row[$key];
                if ($normalize) {
                    unset($row[$key]);
                    if (count($row) == 1) {
                        $row = array_shift($row);
                    }
                }
                $rows[$index] = $row;
            }
            return (array) $rows;
        } else {
            return (array) $this->array;
        }
    }
}
