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
class waContactSelectField extends waContactField
{
    /** Options for this select. array(id => name). Ids are stored in DB, and names are shown to user.
      * Default implementation uses 'options' parameter passed to constructor in $options
      * Could be redefined in subclasses to implement custom option list (e.g. from database).
      * @param $id String (optional, default null) id to return name for.
      * @return mixed If $id is specified returns string with the name for this id. Id $id is null returns array(id => name) with all available options. */
    function getOptions($id = null) {
        if (!isset($this->options['options']) || !is_array($this->options['options'])) {
            throw new waException('No options for waContactSelectField');
        }

        if ($id !== null) {
            if (!isset($this->options['options'][$id])) {
                throw new Exception('Unknown id: '.$id);
            }
            return $this->options['options'][$id];
        }

        return $this->options['options'];
    }

    public function getInfo() {
        $data = parent::getInfo();
        $data['options'] = $this->getOptions();

        // In JS we cannot rely on the order of object properties during iteration
        // so we pass an order of keys as an array
        $data['oOrder'] = array_keys($data['options']);
        $data['defaultOption'] = _ws($this->getParameter('defaultOption'));
        return $data;
    }

    /**
     * Return 'Select' type, unless redefined in subclasses
     * @return string
     */
    public function getType()
    {
        return 'Select';
    }
}

// EOF