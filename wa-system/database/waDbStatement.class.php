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
     * @param array $places_map
     */
    private $places_map    = array();

    /**
     * Values to replace placeholders
     * @param array $binded_params
     */
    private $binded_params = array();

    /**
     * Model
     * @var waModel $model
     */
    protected $model = null;

    /**
     * Query
     * @param string $query
     */
    protected $query = '';

    /**
     * @param waModel $model
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
     * @throws waException
     */
    private function prepareQuery()
    {
        if ($this->query == '') {
            throw new waException('Empty query');
        }

        $matches = array();

        if (preg_match_all('/([sibfl]?)(\?|:[a-zA-Z0-9_]+)/', $this->query, $matches, PREG_OFFSET_CAPTURE)) {
            $unnamed_count = 0;
            foreach ($matches[0] as $id => $match) {
                $match[2] = $matches[1][$id][0];
                $match[3] = $matches[2][$id][0];
                if (preg_match('/[a-z0-9]/i', substr($this->query, $match[1] - 1, 1))) {
                    continue;
                }
                $p_name = ($match[3][0] === ':') ? ltrim($match[3], ':') : $unnamed_count++;
                $this->places_map[$p_name]['placeholder'] = $match[0];
                $this->places_map[$p_name]['offset'][] = $match[1];
                $this->places_map[$p_name]['type'] = $match[2];
            }
        }
    }

    /**
     * Assembly
     * @return string $query
     */
    public function getQuery()
    {
        /* No placeholders */
        if (empty($this->places_map)) {
            return $this->query;
        }

        $this->checkParams();

        /* With placeholders */
        $query = $this->query;
        $placeholders = array();
        foreach ($this->places_map as $place_name=>$place) {
            switch ($place['type']) {
                case 'i':
                    if (is_array($this->binded_params[$place_name])) {
                        $replaced_value = array();
                        foreach ($this->binded_params[$place_name] as $v) {
                            $replaced_value[] = (int)$v;
                        }
                        $replaced_value = implode(",", $replaced_value);
                    } else {
                        $replaced_value = (int)$this->binded_params[$place_name];
                    }
                    break;
                case 'b':
                    $replaced_value = ((bool)$this->binded_params[$place_name]) ? 1 : 0;
                    break;
                case 'l':
                    $replaced_value = str_replace(array('%', '_', '\\'), array('\%', '\_', '\\\\'), $this->escape($this->binded_params[$place_name]));
                    break;
                case 'f':
                    $replaced_value = str_replace(',', '.', (float)$this->binded_params[$place_name]);
                    break;
                case 's':
                default:
                    if (is_array($this->binded_params[$place_name])) {
                        $replaced_value = array();
                        foreach ($this->binded_params[$place_name] as $v) {
                            $replaced_value[] = "'".$this->escape($v)."'";
                        }
                        $replaced_value = implode(",", $replaced_value);
                    } else {
                        $replaced_value = $this->binded_params[$place_name] === null ? 'NULL' : "'". $this->escape($this->binded_params[$place_name]) ."'";
                    }
            }

            foreach($place['offset'] as $offset) {
                $placeholders[$offset] = array('placeholder' => $place['placeholder'], 'value' => $replaced_value);
            }
        }

        ksort($placeholders);

        $offset_increment = 0;
        foreach ($placeholders as $current_offset => $placeholder) {
            $offset = $current_offset + $offset_increment;
            $placeLength = strlen($placeholder['placeholder']);
            $query = substr($query, 0, $offset) . $placeholder['value'] . substr($query, $offset+$placeLength);
            $offset_increment = (($offset_increment - $placeLength) + strlen($placeholder['value']));
        }
        return $query;
    }

    private function checkParams()
    {
        if(count($this->places_map) > count($this->binded_params)) {
            $error = "Insufficient params: ".wa_dump_helper($this->binded_params).
                    "\nfor query:\n" . $this->query;
            throw new waDbException($error);
        }
        return true;
    }

    /**
     * @param mixed $param
     * @param mixed $value
     * @throws waException
     * @return bool
     */
    public function bindParam($param, &$value)
    {
        if(!is_string($param) && !is_integer($param)) {
            throw new waException('Illegal name/place of the placeholder.');
        }
        if (is_object($value) || is_array($value)) {
            throw new waException('Illegal value of the placeholder.');
        }

        if(isset($this->places_map[$param])) {
            $this->binded_params[$param] = $value;
            return true;
        }

        return false;
    }

    /**
     * @param $params_array
     * @throws waException
     * @return bool
     */
    public function bindArray($params_array)
    {
        if (!is_array($params_array)) {
            throw new waException('Invalid arguments passed');
        }

        foreach ($params_array as $param => $value){
            if (isset($this->places_map[$param])) {
                $this->binded_params[$param] = $value;
            }
        }

        return true;
    }

    /**
     * Execute query and returns object of the result
     * @param array $params
     * @return waDbResultSelect|waDbResultInsert|waDbResultDelete $dbresult
     */
    public function query(array $params = array())
    {
        $this->bindArray($params);
        return $this->model->query($this->getQuery());
    }

    /**
     * Execute query without creating of the result object
     * @param array $params
     * @return boolean
     */
    public function exec(array $params = array())
    {
        $this->bindArray($params);
        return $this->model->exec($this->getQuery());
    }

    /**
     * Escapes special characters in a string for use in an SQL statement
     *
     * @param string
     * @return string
     */
    public function escape($value)
    {
        if (is_float($value)) {
            return str_replace(',', '.', (float)$value);
        }
        return $this->model->escape($value);
    }
}

