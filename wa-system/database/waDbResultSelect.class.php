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
     * Returns the contents of the first found record as a combination of a zero-indexed and associative array.
     *
     * @return array
     */
    public function fetch()
    {
        return $this->iterator->fetch();
    }

    /**
     * Returns the contents of the first found record as a zero-indexed array.
     *
     * @return array
     */
    public function fetchArray()
    {
        return $this->iterator->fetchArray();
    }

    /**
     * Returns the contents of the first found record as an associative array.
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
     * Returns the value of the specified field in the first found record.
     *
     * @param bool|string $field Optional database field name whose value must be returned. If not specified, the value
     *     of the first database field is returned.
     * @param bool|int $seek Flag requiring to return the value of the next found database record next time this method is called.
     * @return bool|mixed Field value, or false if no data are found
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
     * Returns the contents of all found database records as an array of sub-arrays corresponding to individual records.
     *
     * @param string $key Name of table field, by whose value table records will be grouped. The keys of the returned
     *     array will be the values of the specified field.
     * @param int|bool $normalize Value grouping mode applied when a non-empty value is specified for $key parameter;
     *     acceptable values for this parameter are these:
     *     - 0 or true: if the table contains more than one record with equal values of the specified field, then only
     *       the first of such records are included in the returned array
     *     - 1 or true: similar to previous mode; additionally, items with keys matching the specified field name are
     *       removed from each subarray
     *     - 2: if the table contains more than one record with equal values of the specified field, then all such
     *       records are included in the returned array so that records with equal values of the specified field are
     *       grouped as items of subarrays whose keys match the values of the specified field; additionally, items with
     *       keys matching the specified field name are removed from each subarray (as described for mode 1/true)
     * @return array
     */
    public function fetchAll($key = null, $normalize = false)
    {
        return $this->getIterator()->export($key, $normalize);
    }

    /**
     * Returns the number of found database records.
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
     * Rewind iterator
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
