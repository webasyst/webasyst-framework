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
class waDbMysqliAdapter extends waDbAdapter
{
	
	const RESULT_ASSOC = MYSQLI_ASSOC;
	const RESULT_NUM = MYSQLI_NUM;
	const RESULT_BOTH = MYSQLI_BOTH;
		
	public function connect($settings)
	{
		$host = $settings['host'];
		$port = isset($settings['port']) ? $settings['port'] : ini_get("mysqli.default_port");
		$handler = @new mysqli($host, $settings['user'], $settings['password'], $settings['database'], $port);
		if ($handler->connect_error) {
			throw new waDbException($handler->connect_error, $handler->connect_errno);
		}
		
        $charset = isset($settings['charset']) ? $settings['charset'] : 'utf8';
		@$handler->query("SET NAMES '" . $charset . "' COLLATE '".$charset."_bin'");
		if (isset($settings['sql_mode'])) {
			$sql = "SET SESSION sql_mode = '".$handler->real_escape_string($settings['sql_mode'])."'";
			@$handler->query($sql);
		}
		return $handler;
	}
	
	/**
	 * Performs a query on the database and return a result object or false
	 * 
	 * @param string $query - SQL-query
	 * @param mysqli $handler - mysqli connection
	 * @return mysqli_result
	 */
	public function query($query, $handler)
	{
		return $handler->query($query);
	}
	
	public function close($handler)
	{
		return $handler->close($handler);
	}
	
	public function num_rows($result)
	{
		return $result->num_rows;
	}
	
	public function free($result)
	{
		return $result->free_result();
	}
	
	public function data_seek($result, $offset)
	{
		return $result->data_seek($offset);
	}	
	
	public function fetch_array($result, $mode = self::RESULT_NUM)
	{
		return $result->fetch_array($mode);
	}
	
	public function fetch_assoc($result)
	{
		return $result->fetch_assoc();
	}	
	
	public function insert_id($handler)
	{
		return $handler->insert_id;
	}
	
	public function affected_rows($handler)
	{
		return $handler->affected_rows;
	}	
	
	public function escape($string, $handler)
	{
		return $handler->real_escape_string($string);
	}

	public function ping($handler)
	{
	    return $handler->ping();
	}
	
	public function error($handler)
	{
		return $handler->error;
	}
	
	public function errorCode($handler)
	{
		return $handler->errno;
	}
	
	public function schema($table, $handler)
	{
    	$res = $this->query("DESCRIBE ".$table, $handler);
    	if (!$res) {
    		$this->exception($handler);
    	} 
   		$result = array();
   		while ($row = $this->fetch_assoc($res)) {
   			$field = array();
   			$i = strpos($row['Type'], '(');
   			if ($i === false) {
   				$field['type'] = $row['Type'];
   				$field['length'] = null;
   			} else {
   				$field['type'] = substr($row['Type'], 0, $i);
   				$field['length'] = substr($row['Type'], $i + 1, -1);
   			}
   			$field['null'] = $row['Null'] == 'YES' ? 1 : 0;
   			$field['default'] = $row['Default'] === 'NULL' ? ($field['null'] ? null : $this->castValue($field['type'], '')) : $row['Default'];
   			$field['extra'] = $row['Extra']; 
   			$result[$row['Field']] = $field;
   		}
   		return $result;		
	}
	
	protected function exception($handler)
	{
		throw new waDbException($this->error($handler), $this->errorCode($handler));
	}
	
}