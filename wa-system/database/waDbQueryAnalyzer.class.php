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

    public function __construct($query)
    {
        $this->query_string = trim($query);
        $type = strtok($this->query_string, " \n\t("); // `(` added as whitespace for `(SELECT ...) UNION (SELECT ...) ORDER BY ...` queries
        $this->type = trim(strtolower($type));
    }

    public function getQueryType()
    {
        return $this->type;
    }

    public function isSelectCount()
    {
        if(preg_match("/^\s*SELECT\s+COUNT\(/ius", $this->query_string))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $query_result
     * @param waDbAdapter $adapter
     * @return waDbResultDelete|waDbResultInsert|waDbResultReplace|waDbResultSelect|waDbResultUpdate
     */
    public function invokeResult($query_result, waDbAdapter $adapter)
    {
        switch($this->type){
            case 'select':
            case 'show':
            case 'desc':
            case 'describe':
                $result = new waDbResultSelect($query_result, $adapter);
                break;
            case 'insert':
            case 'replace':
                $result = new waDbResultInsert($query_result, $adapter);
                break;
            case 'update':
                $result = new waDbResultUpdate($query_result, $adapter);
                break;
            case 'delete':
                $result = new waDbResultDelete($query_result, $adapter);
                break;
            default:
                return $query_result;
        }
        return $result;
    }
}

