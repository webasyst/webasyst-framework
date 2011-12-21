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
    	$this->handler = $this->connect($this->settings);
    }

    abstract public function connect($settings);

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

    abstract public function error();

    abstract public function errorCode();    

    abstract public function insert_id();

    abstract public function affected_rows();

    abstract public function schema($table);
    
    public function getIterator($result)
    {
        return new waDbResultIterator($result, $this);
    }
}