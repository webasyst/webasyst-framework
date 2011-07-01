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
abstract class waDbResult
{

    /**
     * @var mysql
     */
    protected $handler;

    /**
     *
     * Enter description here ...
     * @var waDbAdapter
     */
    protected $adapter;
    /**
     * @var mixed
     */
    protected $result;

    /**
     *
     * @final
     * @param mysql $mysql
     * @param mixed $result
     */
    final function __construct($handler, $result, $adapter)
    {
        $this->result = $result;
        $this->handler = $handler;
        $this->adapter = $adapter;
        $this->onConstruct();
    }


    protected function onConstruct() {}

    /**
     * Overload calling of the methods for debug
     *
     * @throws DbResultException
     * @param string $method
     * @param array $args
     */
    final public function __call($method, $args)
    {
        throw new Exception('Undefined method: '.$method);
    }
}
