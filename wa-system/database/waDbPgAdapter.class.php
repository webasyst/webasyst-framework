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
class waDbPgAdapter extends waDbAdapter
{
	public function connect($settings)
	{
		$connection = "host=".$settings['host'];
		$connection.= " dbname=".$settings['database'];
		$connection.= " user=".$settings['user'];
		$connection.= " password=".$settings['password'];
		return pg_connect($connection);
	}
	
	public function query($query, $handler)
	{
		return pg_query($handler, $query);
	}
	
	public function close($handler)
	{
		
	}
	
	public function num_rows($result)
	{
		
	}
	
	public function fetch_array($result)
	{
		return pg_fetch_array($result);
	}
	
	public function fetch_assoc($result)
	{
		return pg_fetch_assoc($result);
	}	
	
	public function escape($string, $handler)
	{
		return pg_escape_string($handler, $string);
	}
	
	public function error($handler)
	{
		return pg_errormessage($handler);
	}
	
	public function errorCode($handler)
	{
		
	}
	
	public function escapeField($string)
	{
		return $string;
	}
	
	public function schema($table, $handler)
	{
		$sql = "select * from INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$table."'";
		$res = pg_query($sql);
		$result = array();
		while ($row = pg_fetch_assoc($res)) {
			$type = $row['data_type'];
			if ($type == 'integer') {
				$type = 'int';
			}
			$result[$row['column_name']] = array(
				'type' => $type
			);
		}
		return $result;
	}	
}