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
     * Текущий индекс выборки.
     * 
     * @var int
     */
    protected $key          = '';
    
    /**
     * Массив данных.
     *
     * @var array
     */
    private $array          = array();
    
    private $iteration      = 0;

    /**
     * Конструктор итератора. Получает в качестве параметра
     * массив данных.
     *
     * @param array $array
     */
    public function __construct($array)
    {
        $this->array    = (array) $array;
    }
    
    /**
     * Текущий элемент массива.
     * 
     * @return mixed
     */
    public function current()
    {
        return current($this->array);
    }
    
    /**
     * Текущий ключ массива.
     * 
     * @return int
     */
    public function key()
    {
        return key($this->array);
    }
    
    /**
     * Сдвигает и возвращает элемент массива.
     * 
     * @return mixed
     */
    public function next()
    {
        next($this->array);
        $this->key = $this->key();
        $this->iteration++;
    }
    
    /**
     * Сброс указателя результата к начальным значениям.
     * 
     */
    public function rewind()
    {
        $this->key = 0;
        $this->iteration = 0;
        return reset($this->array);
    }
    
    /**
     * Проверка текущей позиции итератора 
     * и количества записей в массиве данных.
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
     * Перемещение указателя к указанной позиции.
     * 
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
     * Возвращает количество записей.
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
    
    public function fetchObject($class)
    {
        $row = $this->current();
        next($this->array);
        $object = new $class();
        foreach ($row as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }
    
    
    /**
     * Возвращает данные записанные в итераторе.
     *
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
