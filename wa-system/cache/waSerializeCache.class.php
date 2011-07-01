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
 * @subpackage cache
 */

class waSerializeCache extends waFileCache 
{
    
	protected function writeToFile($file, $v)
	{
		$data = serialize(array('time' => time(), 'ttl' => $this->ttl, 'value' => $v));
		return file_put_contents($file, $data);
	}

	protected function readFromFile($file)
	{
		if (file_exists($file)) {
			$info = unserialize(file_get_contents($file));
	        if (!empty($info['ttl']) && time() - $info['time'] >= $info['ttl']) {
	        	return null;
	        } else {
	        	return $info['value'];
	        }
		}
		return null;
	}	
}