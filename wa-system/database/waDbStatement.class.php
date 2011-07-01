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

/**
 * DbStatement
 * Use typed placeholders.
 * Allowed 2 formats:
 * <ul>
 * <li>[sibf]? - not named<br>[sibf] - type of the data</li>
 * <li>[sibf]:placeholderName - named, </li>
 * </ul>
 *
 * Allowed types:
 * <pre>
 *  's' - String,
 *  'i' - Integer,
 *  'b' - Boolean,
 *  'f' - Float.
 * </pre>
 * If type is not specified, placeholder use as 's'.
 */
final class waDbStatement
{

    /**
     * Placeholders
     * @param array $placesMap
     */
    private $placesMap    = array();

    /**
     * Values to replace placeholders
     * @param array $bindedParams
     */
    private $bindedParams = array();

    /**
     * Model
     * @var DbModel $model
     */
    protected $model = null;

    /**
     * Query
     * @param string $query
     */
    protected $query = '';

    /**
     * @param DbModel $model
     * @param string $query
     */
    function __construct(waModel $model, $query)
    {
        $this->model = $model;
        $this->query = $query;
        $this->prepareQuery();
    }

    /**
     * Preparing query to the next operations
     */
    private function prepareQuery()
    {
        if($this->query == '')
        {
            throw new MySQLException('Empty query');
        }

        $matches = array();

        if(preg_match_all('/([sibf]?)(\?|:[A-z0-9_]+)/', $this->query, $matches, PREG_OFFSET_CAPTURE))
        {
            $unnamedCount = 0;
            foreach ($matches[0] as $id=>$match)
            {
                $match[2] = $matches[1][$id][0];
                $match[3] = $matches[2][$id][0];
                $pName = ($match[3][0] === ':') ? ltrim($match[3], ':') : $unnamedCount++;
                $this->placesMap[$pName]['placeholder'] = $match[0];
                $this->placesMap[$pName]['offset'][]    = $match[1];
                $this->placesMap[$pName]['type']        = $match[2];
            }
        }
    }


    /**
     * Assembly
     * @return string $query
     */
    private function assemblyQuery()
    {
        /* No placeholders */
        if(empty($this->placesMap)){
            return $this->query;
        }
        /* With placeholders */
        $query = $this->query;
        $placeholders = array();
        foreach($this->placesMap as $placeName=>$place)
        {
            switch($place['type']) {
                case 'i':
                    if (is_array($this->bindedParams[$placeName])) {
                        $replacedValue = array();
                        foreach ($this->bindedParams[$placeName] as $v) {
                            $replacedValue[] = (int)$v;
                        }
                        $replacedValue = implode(",", $replacedValue);
                    } else {
                        $replacedValue = (int)$this->bindedParams[$placeName];
                    }
                    break;
                case 'b':
                    $replacedValue = ((bool)$this->bindedParams[$placeName]) ? 1 : 0;
                    break;
                case 'f':
                    $replacedValue = str_replace(',', '.', (float)$this->bindedParams[$placeName]);
                    break;
                case 's':
                default:
                    if (is_array($this->bindedParams[$placeName])) {
                        $replacedValue = array();
                        foreach ($this->bindedParams[$placeName] as $v) {
                            $replacedValue[] = "'".$this->escape($v)."'";
                        }
                        $replacedValue = implode(",", $replacedValue);
                    } else {
                        $replacedValue = $this->bindedParams[$placeName] === null ? 'NULL' : "'". $this->escape($this->bindedParams[$placeName]) ."'";
                    }
            }

            foreach($place['offset'] as $offset)
            {
                $placeholders[$offset] = array('placeholder' => $place['placeholder'], 'value' => $replacedValue);
            }
        }

        ksort($placeholders);

        $offsetIncrement = 0;
        foreach($placeholders as $current_offset => $placeholder)
        {
            $offset = $current_offset + $offsetIncrement;
            $placeLength = mb_strlen($placeholder['placeholder']);
            $query = mb_substr($query, 0, $offset) . $placeholder['value'] . mb_substr($query, $offset+$placeLength);
            $offsetIncrement = (($offsetIncrement - $placeLength) + mb_strlen($placeholder['value']));
        }
        return $query;
    }

    private function checkParamsFilling()
    {
        if(count($this->placesMap) > count($this->bindedParams))
        {
            $error = "Bad params: \n" .
                     "Placeholder's params: \n" .
                     var_export($this->placesMap) . "\n" .
                     "Bind params: \n" .
                     var_export($this->bindedParams) . "\n";

            throw new Exception($error);
        }
        return true;
    }

    /**
     * @param mixed $param
     * @param mixed $value
     * @return bool
     */
    function bindParam($param, &$value)
    {
        if(!is_string($param) && !is_integer($param)) {
            throw new Exception('Illegal name/place of the placeholder.');
        }
        if(is_object($value) || is_array($value)) {
            throw new Exception('Illegal value of the placeholder.');
        }

        if(isset($this->placesMap[$param])) {
            $this->bindedParams[$param] = &$value;
            return true;
        }

        return false;
    }

    /**
     * @param array $paramsArray
     * @return bool
     */
    function bindArray($paramsArray)
    {
        if (!is_array($paramsArray)) {
            throw new Exception('В качестве параметра функции необходим массив.');
        }

        foreach($paramsArray as $param => $value){
            if(isset($this->placesMap[$param])) {
                $this->bindedParams[$param] = $value;
            }
        }

        return true;
    }

    /**
     * Execute query and returns object of the result
     * @return DbResultSelect|DbResultInsert|DbResultDelete $dbresult
     */
    function query(array $params = array())
    {
        $this->bindArray($params);
        $this->checkParamsFilling();
        return $this->model->query($this->assemblyQuery());
    }

    /**
     * Execute query without creating of the result object
     * @return boolean
     */
    function exec(array $params = array())
    {
        $this->bindArray($params);
        $this->checkParamsFilling();
        return $this->model->exec($this->assemblyQuery());
    }

    /**
     * Escape string
     */
    function escape($value)
    {
        return $this->model->escape($value);
    }
}

