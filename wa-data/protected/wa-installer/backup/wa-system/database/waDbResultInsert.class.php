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
class waDbResultInsert extends waDbResultDelete
{

    /**
     * @var int $insert_id
     */
    protected $insert_id;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->insert_id = $this->adapter->insert_id();
    }

    /**
     * @return int insert_id
     */
    public function lastInsertId()
    {
        return $this->insert_id;
    }
}
