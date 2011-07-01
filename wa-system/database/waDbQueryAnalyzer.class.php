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
class waDbQueryAnalyzer
{
    protected $query_string;
    protected $type = false;

    const QUERY_SELECT  = 'select';
    const QUERY_INSERT  = 'insert';
    const QUERY_REPLACE = 'replace';
    const QUERY_DELETE  = 'delete';
    const QUERY_UPDATE  = 'update';

    /**
     * Query benchmarking
     *
     * @var DbQueryBenchmark
     */
    //protected $DbQueryBenchmark;

    /**
     * mysql
     *
     * @var resource
     */
    protected $handler;

    function __construct($query)
    {
        $this->query_string = trim($query);
        $type = substr($this->query_string, 0, strpos($this->query_string, ' '));
        $this->type = trim(strtolower($type));
    }

    function getQueryType()
    {
        return $this->type;
    }

    function isSelectCount()
    {
        if(preg_match("/^\s*SELECT\s+COUNT\(/ius", $this->query_string))
        {
            return true;
        }

        return false;
    }

    function invokeResult($query_result, $handler, waDbAdapter $adapter)
    {
        switch($this->type){
            case 'select':
            case 'show':
            case 'desc':
            case 'describe':
                $result = new waDbResultSelect($handler, $query_result, $adapter);
                break;
            case 'insert':
            case 'replace':
                $result = new waDbResultInsert($handler, $query_result, $adapter);
                break;
            case 'update':
                $result = new waDbResultUpdate($handler, $query_result, $adapter);
                break;
            case 'delete':
                $result = new waDbResultDelete($handler, $query_result, $adapter);
                break;
            case 'replace':
                $result = new waDbResultReplace($handler, $query_result, $adapter);
                break;
            default:
                return $query_result;
        }
        return $result;
    }
}

