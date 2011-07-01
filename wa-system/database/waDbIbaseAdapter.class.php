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
class waDbIbaseAdapter extends waDbAdaptor
{
	
	public function connect($settings)
	{
		return ibase_connect($settings['database'], $settings['user'], $settings['password'], $settings['charset']);
	}
	
	public function query($query, $handler)
	{
		return ibase_query($handler, $query);
	}
	
	
	public function fetch_assoc($result)
	{
		return ibase_fetch_assoc($result);
	}
	
	public function close($handler)
	{
		return ibase_close($handler);
	}
}