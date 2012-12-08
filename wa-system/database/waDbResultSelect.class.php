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
class waDbResultSelect extends waDbResult implements IteratorAggregate
{

    public $time;
    /**
     * Iterator
     *
     * @var waDbResultIterator
     */
    protected $iterator;
    
    /**
     * Preload
     */
    protected function onConstruct()
    {
        $this->iterator = $this->adapter->getIterator($this->result);
    }
    
    /**
     * Get Itterator
     *
     * @return waDbResultIterator
     */
    public function getIterator()
    {
        $this->iterator->rewind();
        return $this->iterator;
    }
    
    /**
     * Returns array 
     * 
     * @return array
     */
    public function fetch()
    {
        return $this->iterator->fetch();
    }
    
    /**
     * Returns array with types of keys
     *
     * @return array
     */
    public function fetchArray()
    {
        return $this->iterator->fetchArray();
    }
    
    /**
     * Returns assoc array
     *
     * @return array
     */
    public function fetchAssoc()
    {
        return $this->iterator->fetchAssoc();
    }
    
    /**
     * Returns numeric array
     *
     * @return array
     */
    public function fetchRow()
    {
        return $this->iterator->fetchArray();
    }

    /**
     * Returns value of the column (field)
     * @param bool|string $field
     * @param bool|int $seek
     * @return bool|mixed
     */
    public function fetchField($field = false, $seek = false)
    {
        // Seek to the need position
        if ($seek !== false) {
            $this->iterator->seek($seek);
        }
        
        $data   = $this->iterator->fetch();
        if (!$data) {
            return false;
        }
        // if field not specified then return first element
        if (!$field && is_array($data)) {
            return array_shift($data);
        }
        return (isset($data[$field])) ? $data[$field] : false;
    }
    
    /**
     * Returns all values
     *
     * @param string $key - use one of the values as array index
     * @param int|bool $normalize
     * @return array
     */
    public function fetchAll($key = null, $normalize = false)
    {
        return $this->getIterator()->export($key, $normalize);
    }
    
    /**
     * Returns numbers of the records
     *
     * @return int
     */
    public function count()
    {
        return $this->iterator->count();
    }
    
    /**
     * Free the resource
     * 
     * @return boolean
     */
    public function free()
    {
        if ($this->iterator instanceof waDbResultIterator) {
            return $this->iterator->free();
        }
        return true;
    }
    
    /**
     * Rewind itterator
     *
     * @return waDbResultSelect
     */
    public function rewind()
    {
        $this->getIterator()->rewind();
        return $this;
    }

    /**
     * Saving data for serialization
     *
     * @return array|void
     */
    public function __sleep()
    {
        $this->iterator  = new waDbCacheIterator($this->iterator->export());
        return array('iterator');
    }

    /**
     * Wakeup
     *
     * @return bool|void
     */
    public function __wakeup()
    {
        return true;
    }
    
    
}
