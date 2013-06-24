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
class waDbResultDelete extends waDbResult
{
    /**
     * Returns numbers of the affected rows
     *
     * @var int
     */
    protected $affected_rows;

    protected function onConstruct()
    {
        $this->affected_rows = $this->adapter->affected_rows($this->result);
    }

    /**
     * @return int
     */
    public function affectedRows()
    {
        return $this->affected_rows;
    }

    /**
     * @return bool
     */
    public function result()
    {
        return $this->result;
    }
}
