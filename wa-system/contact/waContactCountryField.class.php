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
class waContactCountryField extends waContactSelectField
{
    protected $model = null;
    
    function getOptions($id = null) {
        if (!$this->model) {
            $this->model = new waCountryModel();
        }
        if ($id) {
            if (! ( $result = $this->model->name($id))) {
                throw new Exception('Unknown country ISO-3 code: '.$id);
            }
            return $result;
        }
        
        $result = $this->model->all();
        foreach($result as &$row) {
            $row = $row['name'];
        }
        return $result;
    }
    
    public function getType() {
        return 'Country';
    }
}

// EOF