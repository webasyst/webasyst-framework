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
 * @subpackage request
 */
class waRequestFileIterator extends waRequestFile implements Iterator,Countable,ArrayAccess
{
    protected $currentIndex = 0;
    protected $indexes = array();

    /** List of uploaded files except the one represented by $this itself */
    protected $files = array();

    public function __construct($input_name)
    {
        if (!isset($_FILES[$input_name])) {
            parent::__construct(null);// no uploaded files
        } else if (!is_array($_FILES[$input_name]['name'])) {
            parent::__construct($_FILES[$input_name]); // one uploaded file
            $this->files[] = &$this;
        } else {
            // Several files with the same input name
            $init = false;
            foreach($_FILES[$input_name]['name'] as $i=>$name){
                $this->indexes[] = $i;
                if($init){
                    $this->files[] = new waRequestFile(array(
                        'name' => $_FILES[$input_name]['name'][$i],
                        'type' => $_FILES[$input_name]['type'][$i],
                        'size' => $_FILES[$input_name]['size'][$i],
                        'tmp_name' => $_FILES[$input_name]['tmp_name'][$i],
                        'error' => $_FILES[$input_name]['error'][$i],
                    ));
                }else{
                    $init = true;
                    parent::__construct(array(
                        'name' => $_FILES[$input_name]['name'][$i],
                        'type' => $_FILES[$input_name]['type'][$i],
                        'size' => $_FILES[$input_name]['size'][$i],
                        'tmp_name' => $_FILES[$input_name]['tmp_name'][$i],
                        'error' => $_FILES[$input_name]['error'][$i],
                    )); // one uploaded file
                    $this->files[] = &$this;
                }
            }
        }
    }

    public function count()
    {
        return count($this->files);
    }

    //
    // Iterator interface
    //

    public function rewind()
    {
        $this->currentIndex = 0;
    }

    /**
     * @return waRequestFile
     */
    public function current()
    {
        return $this->files[$this->currentIndex];
    }

    public function key()
    {
        return $this->indexes[$this->currentIndex];
    }

    public function next()
    {
        $this->currentIndex++;
    }

    public function valid()
    {
        return isset($this->files[$this->currentIndex]);
    }

    /**
     * @param offset
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset,$this->indexes);
    }

    /**
     * @param offset
     */
    public function offsetGet($offset)
    {
        return $this->files[array_search($offset,$this->indexes)];
    }

    /**
     * @param offset
     * @param value
     */
    public function offsetSet($offset, $value)
    {
        if ($value instanceof waRequestFile) {
            $index = array_search($offset,$this->indexes);
            if($index === false){
                $this->indexes[] = $offset;
                $this->files[] = $value;
            }else{
                $this->files[$index] = $value;
            }
        } else {
            throw new waException('Invalid argument type');
        }
    }

    /**
     * @param offset
     */
    public function offsetUnset($offset)
    {
        $index = array_search($offset,$this->indexes);
        if ($index !== false) {
            unset($this->files[$index]);
            unset($this->indexes[$index]);
        }
    }
}

// EOF