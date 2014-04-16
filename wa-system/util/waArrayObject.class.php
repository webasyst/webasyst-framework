<?php
/**
 * Implements smart multidimensional key => value storage that can be accessed both by array access
 * and by field acces interfaces, including modification of deep layers.
 *
 * $a = new waArrayObject();
 *
 * // something simple...
 * $a->q = 1;
 * echo $a['q']; // == 1
 *
 * // tricky
 * $a['w']['e']['r']['t'] = 2;
 * echo $a->w['e']->r->t; // == 2
 *
 * // more magic
 * $a->e = array('r' => array('t' => 3));
 * echo $a->e->r->t; // == 3
 */
class waArrayObject implements ArrayAccess, IteratorAggregate, Countable 
{
    /**
     * Convert native arrays and stdObjects to waArrayObject.
     * Other values are returned as is.
     * @param mixed $data
     * @return mixed
     */
    public static function convert($data)
    {
        if (is_array($data) || (is_object($data) && get_class($data) == 'stdClass')) {
            return new self($data);
        }
        return $data;
    }

    /** @param Traversable $data */
    public function __construct($data = array())
    {
        $this->setAll($data);
    }

    /**
     * Append all key => value pairs from given Traversabel to $this
     * @param array $data
     * @return $this
     */
    public function setAll($data)
    {
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }
        return $this;
    }

    /** @return int number of key => value pairs in this array */
    public function count()
    {
        return count($this->removeStubs()->rec_data);
    }

    /**
     * Convert this object to a native array.
     * Will currently cause infinite recursion for self-containing structures.
     * @return array
     */
    public function toArray()
    {
        $data = $this->rec_data;
        foreach ($data as $key => $value) {
            if ($value instanceof self) {
                if ($value->stub()) {
                    unset($data[$key]);
                    unset($this->rec_data[$key]);
                } else {
                    $data[$key] = $value->toArray();
                }
            }
        }
        return $data;
    }

    /** Remove all key => value pairs from this array
     * @return $this */
    public function clear()
    {
        $this->rec_data = array();
        return $this;
    }

    /** same as array_key_exists for native arrays */
    public function keyExists($name)
    {
        return array_key_exists($name, $this->removeStubs($name)->rec_data);
    }

    /** @return $this[$name] or null if not set */
    public function ifset($name, $default=null)
    {
        if (!isset($this[$name])) {
            return $default;
        }
        return $this[$name];
    }

    /** @return $this[$name] or null if empty */
    public function ifempty($name, $default=null)
    {
        if (empty($this[$name])) {
            return $default;
        }
        return $this[$name];
    }

    // required to support deep copies
    public function __clone()
    {
        foreach ($this->rec_data as $key => $value) {
            if ($value instanceof self) {
                if ($value->stub()) {
                    unset($this->rec_data[$key]);
                } else {
                    $this[$key] = clone $value;
                }
            }
        }
    }

    //
    // Field access interface
    //

    public function &__get($name)
    {
        if (!array_key_exists($name, $this->rec_data)) {
            $this->rec_data[$name] = new self();
            $this->rec_data[$name]->stub(true);
        }
        return $this->rec_data[$name];
    }

    public function __set($name, $value)
    {
        $value = self::convert($value);
        if (!($value instanceof self) || !$value->stub()) {
            $this->stub(false);
        }
        if ($name === null) { // for array access
            $this->rec_data[] = $value;
        } else {
            $this->rec_data[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->removeStubs($name)->rec_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->rec_data[$name]);
    }

    //
    // Array access interface (copied from field access)
    //

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $data)
    {
        $this->__set($offset, $data);
    }

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    //
    // IteratorAggregate interface
    //

    public function getIterator()
    {
        return new ArrayIterator($this->rec_data);
    }

    //
    // Internal helpers and data storage
    //

    /**
     * Data storage for array/field access.
     * All native arrays and stdObjects are replaced with waArrayObjects before
     * saving into this
     * @var array
     */
    protected $rec_data = array();

    /**
     * Indicates whether this object was created as a stub or intentionally.
     * E.g. after
     *     isset($this->a->b)
     * $this->a is a stub that should be hidden from the outer code.
     *
     * Object is a stub if $this->stub == true and there's no non-stubs in $this->rec_data
     */
    protected $stub = false;

    /** @return boolean see $this->stub */
    protected function stub($val = null)
    {
        if ($val === null) {
            if (!$this->stub) {
                return false;
            }

            foreach($this->rec_data as $k => $v) {
                if (!($v instanceof self) || !$v->stub()) {
                    $this->stub = false;
                    return false;
                }
            }

            return true;
        } else {
            return $this->stub = $val;
        }
    }

    /**
     * Ensure that given key in rec_data is not a stub; when called with no parameters, removes all stubs.
     * @return $this
     */
    protected function removeStubs($key = null)
    {
        if ($key !== null) {
            if (isset($this->rec_data[$key]) && $this->rec_data[$key] instanceof self && $this->rec_data[$key]->stub()) {
                unset($this->rec_data[$key]);
            }
            return $this;
        }

        foreach ($this->rec_data as $k => $v) {
            $this->removeStubs($k);
        }

        return $this;
    }

    public function __toString()
    {
        if ($this->stub()) {
            return '';
        }
        return 'waArrayObject';
    }
}

