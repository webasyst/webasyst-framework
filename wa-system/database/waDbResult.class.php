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
     * Database adapter
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
    final function __construct($result, $adapter)
    {
        $this->result = $result;
        $this->adapter = $adapter;
        $this->onConstruct();
    }
    
    
    public function getResult()
    {
        return $this->result;
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
