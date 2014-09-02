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
 * @subpackage contact
 */
class waContactDataTextStorage extends waContactDataStorage
{
    /**
     * @var waContactDataTextModel
     */
    protected $model;

    /**
     * Returns model
     *
     * @return waContactDataModel|waContactDataTextModel
     */
    public function getModel()
    {
        if (!$this->model) {
            $this->model = new waContactDataTextModel();
        }
        return $this->model;        
    }    
}

// EOF