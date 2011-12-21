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
class waDbSQLite3Adapter extends waDbAdapter
{
	
	const RESULT_ASSOC = SQLITE3_ASSOC;
	const RESULT_NUM = SQLITE3_NUM;
	const RESULT_BOTH = SQLITE3_BOTH;	
	
	public function connect($settings)
	{
		return new SQLite3($settings['database']);
	}
	
	public function query($query)
	{
		return $this->handler->query($query);
	}
	
	public function close()
	{
		return $this->handler->close();
	}
	
	public function free($result)
	{
		return $result->finalize();
	}
	
	public function data_seek($result, $offset)
	{
		$result->reset();
		while ($offset--) {
			$result->fetchArray();
		}
		return true;
	}		
	
	public function num_rows($result)
	{
	 	$num_rows = 0;
      	while($result->fetchArray()) {
        	$num_rows++;
      	}		
      	$result->reset();
      	return $num_rows;
	}
	
	public function fetch_array($result, $mode = self::RESULT_NUM)
	{
		return $result->fetchArray($mode);
	}
		
	public function insert_id()
	{
		return $this->handler->lastInsertRowID();		
	}
	
	public function affected_rows()
	{
		return $this->handler->changes();
	}
	
	public function escape($string)
	{
		return $this->handler->escapeString($string);
	}	
	
	public function error()
	{
		return $this->handler->lastErrorMsg();	
	}
	
	public function errorCode()
	{
		return $this->handler->lastErrorCode();
	}	
	
	public function schema($table)
	{
		$res = $this->handler->query("SELECT * FROM sqlite_master WHERE name = '".$table."'");
		$row = $res->fetchArray();
		if (!$row) {
			return array();
		}
		
		$sql = $row['sql'];
		preg_match("/\((.*)\)/is", $sql, $match);
		$fields = explode(",", $match[1]);
		
		$result = array();
		foreach ($fields as $f) {
			$m = explode(" ", trim($f), 3);
			$field = array(
				'type' => strtolower($m[1]),
				'extra' => isset($m[2]) ? $m[2] : ''
			);
  			$i = strpos($field['type'], '(');
   			if ($i === false) {
   				$field['length'] = null;
   			} else {
   				$field['type'] = substr($field['type'], 0, $i);
   				$field['length'] = substr($field['type'], $i + 1, -1);
   			}
   			if ($field['type'] == 'integer') {
   				$field['type'] = 'int';
   			}
			$result[$m[0]] = $field;
		}
		return $result;
	}
}