<?php

/**
  * Subclass of waArrayObject that allows to remember persistent state to be able
  * to revert back to it or show difference between current and persistent states.
  *
  * Persistent data is stored in $this->persistent. New data is stored in $this->rec_data.
  * Removed keys are stored in $this->removed. Changes to sub-arrays are stored in $this->rec_data
  * as instances of self.
  *
  * $this->rec_data contains instances of waArrayObject to represent arrays and instances of self
  * to represent changed arrays that have corresponding arrays in persistent data too.
  */
class waArrayObjectDiff extends waArrayObject
{
    /** @var waArrayObject persistent data storage */
    protected $persistent = null;

    /** @var array (key => true) list of keys that are unset from $this since the last merge */
    protected $removed = array();

    //
    // Public interface
    //

    /**
     * Note that when waArrayObject is passed as a constructor, it will be changed by $this->merge().
     * @param waArrayObject|array initial persistent data.
     */
    public function __construct($persistent = array())
    {
        if ($persistent instanceof parent) {
            $this->persistent = $persistent;
        } else {
            $this->persistent = new parent($persistent);
        }
    }

    /**
     * Merges changed data into persistent data.
     * @return $this
     */
    public function merge()
    {
        // save changed data
        foreach($this->rec_data as $k => $v) {
            if ($v instanceof self) {
                $v->merge(); // this changes $this->persistent[$k]
            } else {
                $this->persistent->__set($k, $v);
            }
        }

        // save removed data
        foreach($this->removed as $k => $v) {
            $this->persistent->__unset($k);
        }

        // clear cache
        return $this->revert();
    }

    /**
     * Changed data only.
     * @param boolean $new true (default) to return new values, false to return old values (including those that were unset)
     * @return array as would have been returned by $this->toArray()
     */
    public function diff($new = true)
    {
        if ($new) {
            // collecting new values
            $data = $this->rec_data;
            foreach ($data as $key => $value) {
                // plain value?
                if (!$value instanceof parent) {
                    continue;
                }
                // stub?
                if ($value->stub()) {
                    unset($data[$key]);
                    unset($this->rec_data[$key]);
                    continue;
                }
                // parent?
                if (!$value instanceof self) {
                    $data[$key] = $value->toArray();
                    continue;
                }
                // instance of self
                $data[$key] = $value->diff($new);
            }
            return $data;
        } else {
            // collecting old values
            $data = array();
            foreach ($this->persistent->rec_data as $key => $value) {
                // unchanged?
                if (!isset($this->rec_data[$key]) && !isset($this->removed[$key])) {
                    continue;
                }

                // changed internally?
                if (isset($this->rec_data[$key]) && $this->rec_data[$key] instanceof self) {
                    $data[$key] = $this->rec_data[$key]->diff($new);
                    continue;
                }

                // plain old value (convert if needed)
                if ($value instanceof parent) {
                    $data[$key] = $value->toArray();
                } else {
                    $data[$key] = $value;
                }
            }
            return $data;
        }
    }

    /**
     * Cancel all changes and revert to persistent state
     * @return $this
     */
    public function revert()
    {
        parent::clear();
        $this->removed = array();
        return $this;
    }

    protected function removeStubs($key=null)
    {
        if ($key && isset($this->removed[$key])) {
            unset($this->rec_data[$key]);
        }
        return parent::removeStubs($key);
    }

    //
    // Override waArrayObject interface functions
    //

    // setAll() does not change

    public function count()
    {
        $this->removeStubs()->persistent->removeStubs();
        // count(persistent + changed - removed)
        return count(array_diff_key($this->persistent->rec_data + $this->rec_data, $this->removed));
    }

    public function toArray()
    {
        return array_diff_key(parent::toArray() + $this->persistent->toArray(), $this->removed);
    }

    public function clear()
    {
        parent::clear();
        $this->removed = array_fill_keys(array_keys($this->persistent->rec_data), true);
        return $this;
    }

    public function keyExists($name)
    {
        return parent::keyExists($name) || $this->persistent->keyExists($name) && !isset($this->removed[$name]);
    }

    public function __clone()
    {
        $this->persistent = clone $this->persistent;
        parent::__clone();
    }

    public function &__get($name)
    {
        // key exists in rec_data?
        if (array_key_exists($name, $this->removeStubs($name)->rec_data)) {
            return $this->rec_data[$name];
        }

        // key is not removed and exists in persistent data?
        if (!isset($this->removed[$name]) && $this->persistent->keyExists($name)) {
            $v = $this->persistent[$name];
            if ($v instanceof parent) {
                $this->rec_data[$name] = new self($v);
                return $this->rec_data[$name];
            }
            return $v;
        }

        // otherwise, return parent stub
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if ($name !== null) {
            unset($this->removed[$name]);
        }
        return parent::__set($name, $value);
    }

    public function __isset($name)
    {
        return parent::__isset($name) || $this->persistent->__isset($name) && !isset($this->removed[$name]);
    }

    public function __unset($name)
    {
        $this->removed[$name] = true;
        parent::__unset($name);
    }

    // ArrayAccess interface does not change

    public function getIterator()
    {
        // is it possible to change persistent data when iterating by reference
        // not sure it it's bad enough to try to fix it
        return new ArrayIterator(array_diff_key($this->rec_data + $this->persistent->rec_data, $this->removed));
    }
}

