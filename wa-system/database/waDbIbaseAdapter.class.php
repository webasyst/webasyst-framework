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
		$handler = ibase_connect($settings['host'].":".$settings['database'], $settings['user'], $settings['password'], $settings['charset']);
		if (!$handler) {
			throw new waDbException($this->error(), $this->errorCode());
		}
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
	
	public function data_seek($result, $offset)
	{
		
	}

	public function num_rows($result)
	{
		return 0;
	}
	
	public function error() 
	{ 
		return ibase_errmsg();
	}
	
	public function errorCode() 
	{ 
		return ibase_errcode();
	}
	
	public function insert_id()
	{
	}
	
	public function affected_rows() 
	{
		return ibase_affected_rows($this->handler);
	}

	public function schema($table) 
	{
		
	}
	
	public function getIterator($result, $handler)
	{
		// 	return new waDbResultIbaseIterator($result, $this);
		$result = array();
		while ($row = $this->fetch($result)) {
			$result = array();
		}
		return new waDbCacheIterator($result);
	}
	
}

class waDbResultIbaseIterator extends waDbResultIterator
{
	protected $temp;
	
	public function rewind()
	{
	    return $this->temp = parent::rewind();
	}	

	public function valid()
	{
	    if (!$this->temp) {
		    $this->temp = $this->fetchAssoc();
	    }
		return $this->temp ? true : false;
	}

	public function next()
	{
		$this->key++;
		if ($this->temp) {
			$this->current = $this->temp;
			$this->temp = null;
		} else {
			$this->current = $this->fetchAssoc();
		}
		return $this->current;
	}
}