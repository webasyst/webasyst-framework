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

class waVarExportCache extends waFileCache
{
	protected function writeToFile($file, $v)
	{
		return file_put_contents($file, "<?php\nreturn ".var_export($v, true).";");
	}

	protected function readFromFile($file)
	{
		if (file_exists($file)) {
			if ($this->ttl && time() - filemtime($file) >= $this->ttl) {
				$this->delete();
				return null;
			} else {
				return include($file);
			}
		}
		return null;
	}		
}