<?php

/**
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @copyright 2014 Serge Rodovnichenko
 * @package wa-system
 * @subpackage file
 */
class waRecursivePhpFilterIterator extends RecursiveFilterIterator
{

    protected $ignore=array();

    public function accept()
    {
        $filename = $this->current()->getFilename();

        if($filename[0] === '.') {
            return FALSE;
        }

        if(isset($this->ignore[$filename])) {
            return FALSE;
        }

        return $this->isDir() || substr($filename, -4) === '.php';
    }

    public function ignore($items)
    {
        if(is_array($items) && !empty($items)) {
            $this->ignore = array_flip($items);
        }
    }
}
