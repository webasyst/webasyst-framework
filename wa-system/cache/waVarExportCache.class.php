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
        if ((!file_exists($file) && is_writable(dirname($file))) || is_writable($file)) {
            return file_put_contents($file, "<?php\nreturn ".var_export($v, true).";\n");
        } elseif (waSystemConfig::isDebug()) {
            throw new waException("Cannot write to cache file ".$file, 601);
        }
    }

    protected function readFromFile($file)
    {
        if (file_exists($file)) {
            if ($this->ttl && time() - filemtime($file) >= $this->ttl) {
                $this->delete();
                return null;
            } else {
                if (!filesize($file)) {
                    $this->delete();
                    return null;
                }
                $r = @include($file);
                // check cache
                if (!$r) {
                    $this->delete();
                    return null;
                }
                return $r;
            }
        }
        return null;
    }
}
