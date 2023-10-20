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
class waModel
{
    const INSERT_ON_DUPLICATE_KEY_UPDATE = 1;
    const INSERT_IGNORE = 2;
    const INSERT_EXPLICIT = 3;

    /**
     * Database Adapter
     * @var waDbAdapter
     */
    protected $adapter;

    /**
     * Writable or not
     * @var boolean
     */
    protected $writable = true;

    /**
     * Name of the table
     * @var string
     */
    protected $table = false;

    protected $fields = array();

    /**
     * Primary key of the table
     * @var string
     */
    protected $id = 'id';

    /**
     * Cache
     * @var waiCache
     */
    private $cache = null;

    /**
     * Array of caches to clean after execution of query INSERT, UPDATE or DELETE
     * @var array
     */
    private $cache_cleaners = array();

    /**
     * Database id in config (db.php), by default "default"
     *
     * @var string
     */
    protected $type;

    //private $readonly   = false;

    /**
     * @param string $type
     * @param bool $writable
     * @throws waDbException|waException
     */
    public function __construct($type = null, $writable = false)
    {
        $this->writable = $writable;
        $this->type = $type ? $type : 'default';
        $this->adapter = waDbConnector::getConnection($this->type, $this->writable);
        if ($this->table && !$this->fields) {
            $this->getMetadata();
        }
    }

    /**
     * Get meta description of the table and generate fields array
     * @return array
     * @throws waDbException|waException
     */
    public function getMetadata()
    {
        if ($this->table && !$this->fields) {
            $runtime_cache = $this->getMetadataCache();
            if ($this->fields = $runtime_cache->get()) {
                return $this->fields;
            }
            if (SystemConfig::isDebug()) {
                $this->fields = $this->getFields();
            } else {
                $cache = new waSystemCache('db/'.$this->type.'/'.$this->table, -1, 'webasyst');
                if (!($this->fields = $cache->get())) {
                    $this->fields = $this->getFields();
                    $cache->set($this->fields);
                }
            }
            $runtime_cache->set($this->fields);
        }
        return $this->fields;
    }

    /**
     * Update and return description of table columns (like getMetadata()), bypassing all caches.
     * @return array
     * @throws waDbException
     */
    public function clearMetadataCache()
    {
        $this->getMetadataCache()->delete();
        $cache = new waSystemCache('db/'.$this->type.'/'.$this->table, -1, 'webasyst');
        $cache->delete();
        $this->fields = null;
        return $this->getMetadata();
    }

    protected function getMetadataCache()
    {
        if (is_scalar($this->type)) {
            $key = $this->type;
        } else {
            if (empty($this->type['metadata_cache_key'])) {
                $this->type['metadata_cache_key'] = uniqid('m', true);
            }
            $key = $this->type['metadata_cache_key'];
        }
        return new waRuntimeCache('db/'.$key.'/'.$this->table, -1, 'webasyst');
    }

    /**
     * Returns array corresponding to all table fields and containing their default values defined in table
     * description file.
     *
     * @return array
     * @throws waDbException|waException
     */
    public function getEmptyRow()
    {
        $result = array();
        foreach ($this->getMetadata() as $fld_id => $fld) {
            if (isset($fld['default'])) {
                $result[$fld_id] = $fld['default'];
            } else {
                $result[$fld_id] = '';
            }
        }
        return $result;
    }

    /**
     * @return array|mixed
     * @throws waDbException|waException
     */
    private function getFields()
    {
        return $this->describe();
        $table = explode('_', $this->table);
        if ($table[0] == 'wa') {
            $app_id = 'webasyst';
        } else {
            $app_id = $table[0];
        }
        $path = wa()->getConfig()->getAppsPath($app_id, 'lib/config/db.php');
        if (file_exists($path)) {
            $schema = include($path);
            $schema = $this->formatSchema($schema);
            $fields = $schema[$this->table];
            foreach ($fields as $key => $f) {
                if (substr($key, 0, 1) == ':') {
                    unset($fields[$key]);
                }
            }
        } else {
            $fields = $this->describe();
        }
        return $fields;
    }

    /**
     * @param string $database
     */
    public function database($database)
    {
        return $this->adapter->select_db($database);
    }

    /**
     * Return description of the table
     *
     * @param string $table
     * @param bool $keys
     * @return array
     * @throws waDbException
     */
    public function describe($table = null, $keys = false)
    {
        if (!$table) {
            $table = $this->table;
        }
        if ($table) {
            return $this->adapter->schema($table, $keys);
        } else {
            return array();
        }
    }

    public function autocommit($flag)
    {
        $this->adapter->autocommit($flag);
    }

    public function commit()
    {
        $this->adapter->commit();
    }

    public function rollback()
    {
        $this->adapter->rollback();
    }

    /**
     * Set cache
     * @param waiCache $cache
     */
    public function setCache(waiCache $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * Add cache to remove
     * @param $cache
     */
    public function addCacheCleaner(waiCache $cache)
    {
        $this->cache_cleaners[] = $cache;
    }

    /**
     * Clean all cachers
     */
    private function cleanCache()
    {
        foreach ($this->cache_cleaners as $cache) {
            /**
             * @var $cache waiCache
             */
            $cache->delete();
        }
        $this->cache_cleaners = array();
    }

    /**
     * Execute SQL query
     *
     * @param string $sql
     * @param bool $unbuffer
     * @throws waDbException
     * @return resource
     */
    private function run($sql, $unbuffer = false)
    {
        $sql = trim($sql);
        $result = $this->adapter->query($sql);
        if (!$result) {
            $error = sprintf(
                "Query Error %d: %s\nQuery: %s",
                $this->adapter->errorCode(),
                $this->adapter->error(),
                $sql
            );
            throw new waDbException($error, $this->adapter->errorCode());
        }
        return $result;
    }

    /**
     * Executes a SQL query without returning its result.
     *
     * @param string $sql SQL query, optionally containing placeholders:
     *     - simple placeholders in the form of '?' characters
     *     - named placeholders in the form type:name
     *         Named placeholder 'type' must be one of the following:
     *         i: integer or array of integers
     *         b: Boolean value, which will be converted to 1 or 0
     *         l: string value, in which % and _ characters will be escaped with backslash \
     *         f: decimal value, in which commas will be replaced by dots.
     *         s: string value or array of strings; its items will be included in quotation marks and concatenated into
     *            a string, separated by comma
     *         Named placeholder 'name' must match one of the keys of $params array.
     *
     * @param mixed $params One or more optional parameters to insert actual values instead of placeholders into SQL query.
     *     For simple placeholders marked as '?', specify additional parameters for exec() method, each parameter per
     *     placeholder, their order matching that of placeholders used in SQL query.
     * @example $model->exec('UPDATE table_name SET name = ? WHERE id = ?', $name, $id);
     *
     *     For named placeholders, $params must contain an associative array of items corresponding to the following coniditions:
     *     a) The key of array item must match one of placeholder names specified in SQL query
     *     b) The value of array item will be inserted into SQL query instead of all placeholders with names matching
     *        the key of this array item.
     *     The order of array items has no importance for named placeholders.
     * @example $data = array('name' => 'John', 'id' => 25,);
     *     $model->exec('UPDATE table_name SET name = s:name WHERE id = i:id', $data);
     *
     * @return resource|bool
     */
    public function exec($sql, $params = null)
    {
        if (!$sql) {
            return true;
        }
        $waDbQueryAnalyzer = new waDbQueryAnalyzer($sql);
        switch ($waDbQueryAnalyzer->getQueryType()) {
            case 'update': case 'replace': case 'delete': case 'drop': case 'insert': $this->cleanCache();
        }

        if (func_num_args() > 2) {
            $params = array_slice(func_get_args(), 1);
        }
        if ($params !== null) {
            if (!is_array($params)) {
                $params = array($params);
            }
            return $this->prepare($sql)->exec($params);
        }

        return $this->run($sql);
    }

    /**
     * Executes an SQL query and returns its result as an instance of class waDbResultSelect, waDbResultInsert,
     * waDbResultUpdate, waDbResultDelete, or waDbResultReplace. To access the result of the SQL query, calling public
     * methods of the class corresponding to the specific query type is necessary.
     *
     * @param string $sql SQL query to be executed, optionally containing placeholders as described for method exec()
     * @param mixed $params
     * @return waDbResultDelete|waDbResultInsert|waDbResultReplace|waDbResultSelect|waDbResultUpdate
     * @throws waDbException
     * @see self::exec()
     */
    public function query($sql)
    {
        $n = func_num_args();
        if ($n > 1) {
            if ($n > 2) {
                $params = array_slice(func_get_args(), 1);
            } else {
                $params = func_get_arg(1);
            }
            if (!is_array($params)) {
                $params = array($params);
            }
            return $this->prepare($sql)->query($params);
        }

        // Define type of the query
        $waDbQueryAnalyzer = new waDbQueryAnalyzer($sql);
        if ($this->cache && $this->cache instanceof waiCache && $waDbQueryAnalyzer->getQueryType() == 'select') {
            $cache = $this->cache->get();
            // if not cached
            if ($cache === null || !$cache instanceof waDbResultSelect) {
                $result = $waDbQueryAnalyzer->invokeResult($this->run($sql), $this->adapter);
                // set cache
                $this->cache->set($result);
                $this->cache = null;

                return $result;
            }
            $this->cache = null;
            return $cache;
        }

        return $waDbQueryAnalyzer->invokeResult($this->run($sql), $this->adapter);
    }

    /**
     * Updates table record with specified value of model's id field value.
     *
     * @param string|int|array $id The value of model's id field, which is searched for across all table records to replace
     *     values of fields specified in $data parameter in the found record.
     * @param array $data Associative array of new values for specified fields of the found record.
     * @param string $options Optional key words for SQL query UPDATE: LOW_PRIORITY or IGNORE.
     * @param bool $return_object Flag requiring to return an instance of class waDbResultUpdate to enable you to call
     *     its public methods to access response received from database server. By default (false)
     *     method returns a Boolean value identifying the successful query result, or null if incorrect
     *     parameters have been passed.
     * @return bool|null|waDbResultUpdate Result of query execution
     */
    public function updateById($id, $data, $options = null, $return_object = false)
    {
        if (!is_array($this->id)) {
            return $this->updateByField($this->id, $id, $data, $options, $return_object);
        } else {
            return $this->updateByField($this->remapIds($id), $data, $options, $return_object);
        }
    }

    /**
     * Updates the contents of records containing the specified field values.
     * Method accepts parameters in two different modes:
     * - for searching values in one field
     * - for searching values in several fields
     *
     * @param string|array $field (single field) Name of the table field whose value needs to be updated.
     *     (multiple fields) Associative array of table fields by whose values table records must be searched.
     * @param string|array $value (single field) Existing value of the specified field, which needs to be replaced with a new
     *     value. Instead of one searched value, you may specify an array; in this case the specified field is updated
     *     for all records where field's value matches one of array items.
     * @param array $data Associative array of new values to be updated for found records.
     * @param string $options Optional key words for SQL query UPDATE: LOW_PRIORITY or IGNORE.
     * @param bool $return_object (single field) Flag requiring to return an instance of class waDbResultUpdate to
     *     enable you to call its public methods to access response received from database server. By default (false)
     *     method returns a Boolean value identifying the successful query result, or null if incorrect
     *     parameters have been passed.
     *
     * @example <pre>
     * // Update by one field and one value
     * $this->updateByField('field_name', $value, $data, $options);
     * // Update by one field and multiple values
     * $this->updateByField('field_name', array($value1, $value2), $data, $options);
     * // Update by multiple fields and values
     * $this->updateByField(array('field1' => $value1, 'field2' => $value2), $data, $options);
     * </pre>
     *
     * @return bool|null|waDbResultUpdate Result of query execution
     */
    public function updateByField($field, $value, $data = null, $options = null, $return_object = false)
    {
        if (is_array($field) && is_array($value)) {
            $return_object = $options;
            $options = $data;
            $data = $value;
            $value = false;
        }
        $where = $this->getWhereByField($field, $value);

        $values = array();
        foreach ($data as $f => $value) {
            if (isset($this->fields[$f])) {
                $values[] = $this->escapeField($f)." = ".$this->getFieldValue($f, $value);
            }
        }

        if ($values) {
            $sql = "UPDATE ".($options ? $options." " : "").$this->table."
                   SET ".implode(", ", $values)."
                   WHERE ".$where;
            if ($return_object) {
                return $this->query($sql);
            } else {
                return $this->exec($sql);
            }
        }
        return null;
    }

    /**
     * Replaces a table record using SQL operator REPLACE.
     *
     * @param array $data Data array
     * @example <pre>array(
     *     array('item_id' => 18, 'order_id' => 984),
     *     array('item_id' => 19, 'order_id' => 976),
     *     ...
     * )</pre>
     * @return resource|bool
     */
    public function replace($data)
    {
        $values = array();
        foreach ($data as $field => $value) {
            if (isset($this->fields[$field])) {
                $values[$this->escapeField($field)] = $this->getFieldValue($field, $value);
            }
        }
        if ($values) {
            $sql = "REPLACE INTO ".$this->table."
                   (".implode(", ", array_keys($values)).") VALUES (".implode(", ", $values).")";
            return $this->exec($sql);
        }
        return true;
    }

    protected function getFieldValue($field, $value)
    {
        if (!isset($this->fields[$field])) {
            // Make sure it's not the cache problem. Someone might have added
            // a new column to the table, but forgot to clear cache.
            $this->clearMetadataCache();
        }
        if (!isset($this->fields[$field])) {
            throw new waException(sprintf('Unknown field %s', $field));
        }
        if (!isset($this->fields[$field]['type'])) {
            throw new waException(sprintf('Incorrect metadata for field %s', $field));
        }
        $type = strtolower($this->fields[$field]['type']);
        if ($value === null && (!isset($this->fields[$field]['null']) || $this->fields[$field]['null'])) {
            return 'NULL';
        }
        if ($type == 'varchar' && !is_array($value) && !is_object($value)) {
            $value = mb_substr((string)$value, 0, $this->fields[$field]['params']);
        }

        return $this->castValue($type, $value, !isset($this->fields[$field]['null']) || $this->fields[$field]['null']);
    }

    /**
     * @param string $type
     * @param string $value
     * @param bool $is_null
     * @return int|mixed|string
     */
    protected function castValue($type, $value, $is_null = false)
    {
        if ($value instanceof waModelExpr) {
            return (string)$value;
        }
        switch ($type) {
            case 'bigint':
            case 'tinyint':
            case 'int':
            case 'integer':
                if (wa_is_int($value)) {
                    return "'".$this->escape($value, 'int')."'";
                } else {
                    return (int) $value;
                }
            case 'decimal':
                if (is_float($value)) {
                    // this fixes an interesting case of 99999999999.9999
                    // if this float is converted to string any other way, it becomes '100000000000'
                    // but var_export keeps it '99999999999.9999'
                    $value = var_export($value, true);
                }
                if (is_string($value) && strpos($value, ',') !== false) {
                    // paranoid mode: when PHP locale is set up to use commas in floats
                    // very likely is not needed in modern PHP environments but keeps lingering in code and tests
                    $value = str_replace(',', '.', $value);
                }
                if (is_numeric($value)) {
                    // if looks like a decimal number, we're done
                    return $value;
                }
                // otherwise, if does not look like a number, then apply brutal sanitation
                // can't just apply to all strings because convertion to PHP double
                // introduces rounding errors - unacceptable for decimal type.
                // breakthrough
            case 'double':
            case 'float':
                if (strpos($value, ',') !== false) {
                    $value = str_replace(',', '.', $value);
                }
                return str_replace(',', '.', (double) $value);
            case 'date':
                if (!$value) {
                    if ($is_null) {
                        return 'NULL';
                    } else {
                        return "'0000-00-00'";
                    }
                }
                return "'".$this->escape($value)."'";
            case 'datetime':
                if (!$value) {
                    if ($is_null) {
                        return 'NULL';
                    } else {
                        return "'0000-00-00 00:00:00'";
                    }
                }
                return "'".$this->escape($value)."'";
            case 'varchar':
            case 'string':
            case 'text':
            default:
                return "'".$this->escape($value)."'";
        }
    }

    /**
     * Inserts a new record into the table.
     *
     * @param array $data Associative array of values with keys matching the names of table fields.
     * @param int $type Execution mode for SQL query INSERT:
     *     0: query is executed without additional conditions (default mode)
     *     1: query is executed with condition ON DUPLICATE KEY UPDATE
     *     2: query is executed with key word IGNORE
     * @return resource|int|bool Returns result object
     *     or id of newly added record (if id field has AUTO_INCREMENT property)
     *     or true (if there are no data to be inserted)
     */
    public function insert($data, $type = 0)
    {
        $values = array();
        foreach ($data as $field => $value) {
            if (isset($this->fields[$field])) {
                $values[$this->escapeField($field)] = $this->getFieldValue($field, $value);
            }
        }
        if ($values) {
            $sql = "INSERT ".($type === 2 ? "IGNORE " : "")." INTO ".$this->table."
                   (".implode(", ", array_keys($values)).") VALUES (".implode(", ", $values).")";
            if ($type === 1 || $type === true) {
                $sql .= " ON DUPLICATE KEY UPDATE ";
                if (is_array($this->id)) {
                    foreach ($this->id as $id) {
                        unset($values[$id]);
                    }
                } else {
                    unset($values[$this->id]);
                }
                $comma = false;
                foreach ($values as $field => $v) {
                    if ($comma) {
                        $sql .= ",";
                    } else {
                        $comma = true;
                    }
                    $sql .= $field." = VALUES(".$field.")";
                }
            }
            if (!$this->isAutoIncrement()) {
                return $this->exec($sql);
            } else {
                return $this->query($sql)->lastInsertId();
            }
        }
        return true;
    }

    /**
     * @deprecated
     * @param $data
     * @return bool
     */
    public function multiInsert($data)
    {
        return $this->multipleInsert($data);
    }

    /**
     * Inserts several records with specified values.
     *
     * @param array $data Array of values to be inserted into the table, which can be specified in several ways:
     *     a) Array items consist of associative subarrays containing items of the form 'field' => 'value'.
     *        Individual records corresponding to each subarray are inserted into the database table.
     * @example <pre>array(
     *     array('name' => 'bag', 'color' => 'red'),
     *     array('name' => 'bag', 'color' => 'green'),
     *     array('name' => 'bag', 'color' => 'blue'),
     * );</pre>
     *
     *     b) Array items have the form 'field' => 'value'. If one of array items contains an array of values, then
     *        multiple records are inserted into the table, each corresponding to one of such subarray items. Otherwise
     *        calling of this method is equivalent to calling method insert().
     * @example <pre>array(
     *     'name'  => 'bag',
     *     'color' => array('red', 'green', 'blue'),
     * );</pre>
     *
     * @param null|int|array $mode Allows to produce 'INSERT IGNORE ...' or 'INSERT ... ON DUPLICATE KEY UPDATE ...' queries
     *     a) if $mode is int and equals to waModel::INSERT_IGNORE it allows to ignore rows with duplicate PRIMARY/UNIQUE keys
     *     b) if $mode is array it contains fields to be updated in case of duplicate PRIMARY/UNIQUE key. See an example
     *        which sets a field 'updated' to the fixed value and updates field 'name' with new value from dataset provided
     *        in the $data array
     *     c) if $mode is int and equals to waModel::INSERT_EXPLICIT: produces basic 'INSERT' statement;
     *        if query fails with 1062 (Duplicate entry for key) then no rows change and waDbException is thrown.
     *     d) Otherwise (default): produces basic 'INSERT' statement; if query fails with 1062 (Duplicate entry for key)
     *        error is silently ignored and no row changed or added.
     * @example <pre>array(
     *      'updated' => '2022-02-22 16:15:11',
     *      'name=VALUES(name)'
     *);<pre>
     *
     * @return resource|bool Returns true if there are no data to be inserted.
     * @throws waException
     */
    public function multipleInsert($data)
    {
        $data = $this->prepareMultipleInsertData($data);
        if (!$data || empty($data['fields']) || empty($data['values'])) {
            return true;
        }

        $args = func_get_args();
        $mode = ifset($args, 1, null);

        if ($mode === null) {
            //return $this->adapter->multipleInsert($this->table, $data['fields'], $data['values']);
        }

        $sql = $this->prepareMultipleInsertQuery($data['fields'], $data['values'], $mode);

        try {
            return $this->query($sql);
        } catch (waDbException $e) {
            if ($mode !== null || $e->getCode() != 1062) {
                throw $e;
            }
        }
    }

    /** Helper for multipleInsert() */
    protected function prepareMultipleInsertQuery(array $fields, array $values, $mode)
    {
        $sql = 'INSERT' . ($mode === self::INSERT_IGNORE ? ' IGNORE' : '');
        $sql .= ' INTO `' . $this->getTableName() .
            '` (' . implode(',', $fields) . ") VALUES\n(" .
            implode("),\n(", $values) .
            ')';

        if (is_array($mode)) {
            $update = [];
            foreach ($mode as $field => $value) {
                if (is_numeric($field) && isset($this->fields[$value])) {
                    $escaped_field = $this->escapeField($value);
                    $update[] = "$escaped_field=VALUES($escaped_field)";
                } elseif (isset($this->fields[$field])) {
                    $update[] = $this->escapeField($field) . "='".$this->escape($value)."'";
                } else {
                    $update[] = $value;
                }
            }
            if ($update) {
                $sql .= "\nON DUPLICATE KEY UPDATE\n" . implode(",\n", $update);
            }
        }

        return $sql;
    }

   /**
     * Helper for multipleInsert()
     * @param array $data
     * @return array
     * @throws waException
     */
    protected function prepareMultipleInsertData(array $data)
    {
        if (!$data) return [];

        $values = array();
        $fields = array();
        if (isset($data[0])) {
            foreach ($data as $row) {
                $row_values = array();
                foreach ($row as $field => $value) {
                    if (isset($this->fields[$field])) {
                        $row_values[$this->escapeField($field)] = $this->getFieldValue($field, $value);
                    }
                }
                if (!$fields) {
                    $fields = array_keys($row_values);
                } else if (count($fields) != count($row_values)) {
                    continue; // silently ignore rows with field count mismatch
                }
                $values[] = implode(',', $row_values);
            }
        } else {
            $multi_field = false;
            $row_values = array();
            foreach ($data as $field => $value) {
                if (isset($this->fields[$field])) {
                    if (is_array($value) && !$multi_field) {
                        $multi_field = $field;
                        $row_values[$this->escapeField($field)] = '';
                    } else {
                        $row_values[$this->escapeField($field)] = $this->getFieldValue($field, $value);
                    }
                }
            }
            $fields = array_keys($row_values);
            if ($multi_field) {
                foreach ($data[$multi_field] as $v) {
                    $row_values[$this->escapeField($multi_field)] = $this->getFieldValue($multi_field, $v);
                    $values[] = implode(',', $row_values);
                }
            } else {
                $values[] = implode(',', $row_values);
            }
        }

        return ['fields' => $fields, 'values' => $values];
    }

    public function isAutoIncrement()
    {
        if ($this->id && !is_array($this->id) && isset($this->fields[$this->id])) {
            return !empty($this->fields[$this->id]['autoincrement']);
        }
        return false;
    }

    /**
     * Returns table contents as an array, each of its items being a subarray corresponding to one of table records.
     *
     * @param string $key Name of table field to group table records by. If non-empty, values of specified (existing)
     *     field are used as keys of the returned array. If empty, method returns default zero-indexed array.
     *
     * @param int|bool $normalize Value grouping mode applied in case of non-empty $key value. Available modes:
     *     0 or false: Values of $key field are used as array keys.
     * @example <pre>$model->getAll('id');
     * array(
     *     3 => array('id' => 3, 'name' => 'John', 'age' => 25),
     *     4 => array('id' => 4, 'name' => 'Mary', 'age' => 25),
     *     5 => array('id' => 5, 'name' => 'Mike', 'age' => 27),
     * )</pre>
     *
     *     1 or true: Values of $key field are used as array keys. Additionally, subarray items with keys matching $key
     *     value are removed from each subarray to avoid returning excessive data.
     * @example <pre>$model->getAll('id', 1);
     * //table has only 2 fields ('id' values are removed from subarrays)
     * array(
     *     3 => 'John',
     *     4 => 'Mary',
     *     5 => 'Mike',
     * )</pre>
     * @example <pre>$model->getAll('id', 1);
     * //table has more than 2 fields ('id' values are removed from subarrays)
     * array(
     *     3 => array('name' => 'John', 'age' => 25),
     *     4 => array('name' => 'Mary', 'age' => 25),
     *     5 => array('name' => 'Mike', 'age' => 27),
     * )</pre>
     *
     *     2: Records containing equal values of $key field are listed as items of subarrays which are included as items
     *     of returned array having $key field values as their keys. Additionally, items with keys matching $key field
     *     name are removed from each subarray (like for 1|true).
     * @example <pre>$model->getAll('age', 2);
     * //'age' field values are used as array keys to group table data
     * array(
     *     array(
     *         25 => array(
     *             0 => array('id' => 3, 'name' => 'John')
     *             1 => array('id' => 4, 'name' => 'Mary')
     *         ),
     *         27 => array(
     *             0 => array('id' => 5, 'name' => 'Mike')
     *         ),
     *     ),
     * )</pre>
     *
     * @return array
     */
    public function getAll($key = null, $normalize = false)
    {
        $sql = "SELECT * FROM ".$this->table;
        return $this->query($sql)->fetchAll($key, $normalize);
    }

    /**
     * Returns the number of records stored in the table.
     *
     * @return int
     */
    public function countAll()
    {
        return $this->query("SELECT COUNT(*) FROM ".$this->table)->fetchField();
    }

    /**
     * Make a string safe to use inside single quotes in SQL statement.
     *
     * @param mixed $data
     * @param string $type - int|like
     * @return string|array
     */
    public function escape($data, $type = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = '';
                }
                $data[$key] = $this->escape($value, $type);
            }
            return $data;
        }

        switch ($type) {
            case 'int':
                if (wa_is_int($data)) {
                    return $this->adapter->escape((string)$data);
                } else {
                    return (int) (string) $data;
                }
            case 'like':
                $data = str_replace('\\', '\\\\', $data);
                return str_replace(array('%', '_'), array('\%', '\_'), $this->adapter->escape($data));
            default:
                return $this->adapter->escape($data);
        }
    }

    protected function escapeField($field)
    {
        return $this->adapter->escapeField($field);
    }

    /**
     * Returns Query Constructor
     *
     * @return waDbQuery
     */
    public function getQueryConstructor()
    {
        return new waDbQuery($this);
    }

    /**
     * Returns table name defined in model class.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table;
    }

    /**
     * Returns the name of id field defined in model class.
     *
     * @return string
     */
    public function getTableId()
    {
        return $this->id;
    }

    /**
     * Returns data from table record with specified value in id field ($this->id).
     *
     * @param int|array $value Field value or array of values
     * @return array|null
     */
    public function getById($value)
    {
        if (is_array($this->id)) {
            $value = $this->remapIds($value);
            $all = (bool)array_filter($value, function($v) {
                return is_array($v);
            });
            return $this->getByField($value, $all ? $this->id : false);
        } else {
            $all = is_array($value);
            return $this->getByField($this->id, $value, $all ? $this->id : false);
        }
    }

    /**
     * Returns data from table records containing specified values of specified fields.
     * Supports 2 modes of passing parameters: to search records by one or by multiple fields.
     *
     * @param string|array $field
     * @param mixed|array|bool $value
     * @param bool|string|int $all
     * @param bool $limit
     * @return array|null
     * @throws waException
     *
     * One field mode:
     *
     * @param string|array Field name or array field name => value
     * @param mixed|array Field value or zero-based array of values.
     * @param bool|string Boolean flag requiring to return data of all found records
     *     or name of field whose values in all found entries must be used as keys of result array.
     *     If false is provided, method returns values only from the first found table record.
     * @param int|bool Maximum number of records to be returned if all records are requested.
     *     By default (false) no limitation is applied.
     *
     * Multiple fields mode:
     *
     * @param array Associative array of field values with field names as keys.
     * @param bool Boolean flag requiring to return data of all found records
     *     or name of field whose values in all found entries must be used as keys of result array.
     *     If false is provided, method returns values only from the first found table record.
     * @param int|bool Maximum number of records to be returned if all records are requested.
     *     By default (false) no limitation is applied.
     *
     * @return array|null
     */
    public function getByField($field, $value = null, $all = false, $limit = false)
    {
        if (is_array($field)) {
            $limit = $all;
            $all = $value;
            $value = false;
        }
        $sql = "SELECT * FROM ".$this->table;
        $sql .= " WHERE ".$this->getWhereByField($field, $value);
        if ($limit) {
            $sql .= " LIMIT ".(int) $limit;
        } elseif (!$all) {
            $sql .= " LIMIT 1";
        }

        $result = $this->query($sql);

        if ($all) {
            return $result->fetchAll(is_string($all) ? $all : null);
        } else {
            return $result->fetchAssoc();
        }
    }

    /**
     * Returns WHERE condition for the SQL query
     *
     * @param string|array $field
     * @param string|array $value
     * @param string|bool  $add_table_name table alias to prefix field names with; true to use original table name.
     * @throws waException when field does not exist.
     * @return string
     */
    protected function getWhereByField($field, $value = null, $add_table_name = false)
    {
        // Several conditions for different fields?
        if (is_array($field)) {
            $add_table_name = $value;

            $where = array();
            foreach ($field as $f => $v) {
                $cond = $this->getWhereByField($f, $v, $add_table_name);
                if ($cond === '1=0') {
                    return '1=0';
                } else if ($cond !== '1=1') {
                    $where[] = $cond;
                }
            }

            if ($where) {
                return implode(" AND ", $where);
            } else {
                return '1=1';
            }
        }

        // Table prefix for field names
        if ($add_table_name) {
            $prefix = is_string($add_table_name) ? $add_table_name."." : $this->table.".";
        } else {
            $prefix = "";
        }

        // Single field, multiple values?
        if (is_array($value)) {
            if (!isset($this->fields[$field])) {
                // Make sure it's not the cache problem. Someone might have added
                // a new column to the table, but forgot to clear cache.
                $this->clearMetadataCache();
            }
            if (!isset($this->fields[$field])) {
                throw new waException(sprintf(_ws('Unknown field %s'), $field));
            }
            if ($value) {
                return $prefix.$this->escapeField($field)." IN ('".implode("','", $this->escape($value))."')";
            } else {
                return '1=0';
            }
        }

        // Single field, single value
        return $prefix.$this->escapeField($field).
                       ($value === null ? " IS NULL" : " = ".$this->getFieldValue($field, $value));
    }

    /**
     * Returns the number of records with the value of the specified field matching the specified value.
     *
     * @param string|array $field Name of field to be checked
     * @param string $value Value to be checked in the specified field of all table records
     * @return int
     *
     * @throws waException
     */
    public function countByField($field, $value = null)
    {
        $sql = "SELECT COUNT(*) FROM ".$this->table;
        $sql .= " WHERE ".$this->getWhereByField($field, $value);
        return $this->query($sql)->fetchField();
    }

    /**
     * Deletes record containing specified value of id field.
     *
     * @param int|array $value Value (or array of values) to be checked in the id field ($this->id) of all table records
     *     to find those which must be deleted.
     * @return bool
     */
    public function deleteById($value)
    {
        if (!is_array($this->id)) {
            return $this->deleteByField($this->id, $value);
        } else {
            return $this->deleteByField($this->remapIds($value));
        }
    }

    /**
     * Deletes records with specified values of specified fields.
     *
     * @param string|array $field Name of one field to be checked or associative array with multiple field names as
     *     item keys and their values as item values.
     * @param int|float|string|array $value Value or array of values to be checked in specified field. Applicable only
     *     for single field name specified in $field parameter; ignored, if array is specified in $field.
     * @return bool Whether deleted successfully
     */
    public function deleteByField($field, $value = null)
    {
        $sql = "DELETE FROM ".$this->table." WHERE ".$this->getWhereByField($field, $value);
        return $this->exec($sql);
    }

    /** Truncates the table, deleting all rows. */
    public function truncate()
    {
        try {
            $this->exec("TRUNCATE {$this->table}");
        } catch (Exception $e) {
            // DB user does not have enough rights for TRUNCATE.
            // Fall back to DELETE. Slow but sure.
            $this->exec("DELETE FROM {$this->table}");
        }
    }

    /**
     * Prepare query and returns object of waDbStatement
     *
     * @param string $sql query
     * @return waDbStatement
     */
    public function prepare($sql)
    {
        return new waDbStatement($this, $sql);
    }

    /**
     * Verifies whether specified field exists in model's table.
     *
     * @param string $field Field name
     * @return bool
     */
    public function fieldExists($field)
    {
        if (is_array($field)) {
            if (empty($field[0]) || empty($field[1]) ||
                !preg_match('/^[a-z0-9_]+$/i', $field[0]) || !preg_match('/^[a-z0-9_]+$/i', $field[1])) {
                return false;
            }
            try {
                $this->query("SELECT ".$this->escapeField($field[1])." FROM ".$field[0]." WHERE 0");
                return true;
            } catch (waDbException $e) {
                return false;
            }
        }
        return isset($this->fields[$field]);
    }

    /**
     * Set select and returns waDbQuery
     *
     * @param string $select
     * @return waDbQuery
     */
    public function select($select)
    {
        $query = new waDbQuery($this);
        return $query->select($select);
    }

    /**
     * Set where and returns waDbQuery
     *
     * @param string $where
     * @param mixed ...$bindings - variadic list of bindings
     * @return waDbQuery
     * @throws waException
     *
     * @example
     *      $model->where('a = ?', 'x')
     *      $model->where('a IN(?)', ['x'])
     *      $model->where('a IN(?)', ['x', 'y'])
     *      $model->where('a = ? AND b = ?', 'x', 'y')
     *      $model->where('a = :a AND b = :b', [
     *          'a' => 'x',
     *          'b' => 'y'
     *      ])
     */
    public function where($where)
    {
        $params = func_get_args();
        $where = array_shift($params);
        $query = new waDbQuery($this);

        // check for this type of calling
        // $model->where('a = :a AND b = :b', [
        //      'a' => 'x',
        //      'b' => 'y'
        // ])
        $is_key_value_binding = count($params) == 1 && is_array($params[0]) && strpos($where, '?') === false;
        if ($is_key_value_binding) {
            return $query->where($where, $params[0]);
        }

        return $query->where($where, $params);


    }

    /**
     * Set order and returns waDbQuery
     *
     * @param string $order
     * @return waDbQuery
     */
    public function order($order)
    {
        $query = new waDbQuery($this);
        return $query->order($order);
    }

    /**
     * Restores connection to the database management server in case of its failure.
     *
     * @return bool Always returns true.
     */
    public function ping()
    {
        if (!$this->adapter->ping()) {
            // reconnect to the server
            $this->adapter->reconnect();
        }
        return true;
    }

    /**
     * @param array $schema - db.php config schema
     *   Schema is associative array for each table with name of table as a key and table-schema as value
     *    <table_name> => array(
             ...
     *    )
     * See db.php format
     */
    public function createSchema($schema)
    {
        $schema = $this->formatSchema($schema);
        $this->adapter->createSchema($schema);
    }

    /**
     * Add column by db.php schema for current table
     *
     * @param string $column
     *
     * @param string[][] $db_schema - db.php config schema
     *   Schema is associative array for each table with name of table as a key and table-schema as value
     *    <table_name> => array(
     *      ...
     *    )
     * See db.php format
     *
     * @param null|string $after_column
     *
     * @param null|string $table - name of table for which need add this column.
     *   If null - get table from model $this->table
     *   Current table must exists in passed $db_schema
     *
     *
     */
    public function addColumn($column, $db_schema, $after_column = null, $table = null)
    {
        $schema = $this->formatSchema($db_schema);
        $table = $table !== null ? $table : $this->table;
        if (isset($schema[$table])) {
            $this->adapter->addColumn($table, $column, $schema[$table], $after_column);
        }
    }

    /**
     * modify column by db.php schema for current table
     *
     * @param string $column
     *
     * @param string[][] $db_schema - db.php config schema
     *   Schema is associative array for each table with name of table as a key and table-schema as value
     *    <table_name> => array(
     *      ...
     *    )
     * See db.php format
     *
     * @param null|string $after_column
     *
     * @param null|string $table - name of table for which need add this column.
     *   If null - get table from model $this->table
     *   Current table must exists in passed $db_schema
     *
     *
     */
    public function modifyColumn($column, $db_schema, $after_column = null, $table = null, $emulate = false)
    {
        $schema = $this->formatSchema($db_schema);
        $table = $table !== null ? $table : $this->table;
        if (isset($schema[$table])) {
            return $this->adapter->modifyColumn($table, $column, $schema[$table], $after_column, $emulate);
        }
    }

    protected function remapIds($id)
    {
        if (!is_array($this->id)) {
            return array($this->id => $id);
        }

        $field = array_fill_keys($this->id, null);
        foreach ($this->id as $n => $name) {
            if (isset($id[$name])) {
                $field[$name] = $id[$name];
            } elseif (isset($id[$n])) {
                $field[$name] = $id[$n];
            }
        }
        return $field;
    }

    private function formatSchema($schema)
    {
        foreach ($schema as $table_id => $table) {
            foreach ($table as $key => $row) {
                if ($key == ':keys') {
                    foreach ($row as $index_id => $index) {
                        if (!is_array($index)) {
                            $schema[$table_id][$key][$index_id] = array('fields' => array($index));
                        } else {
                            foreach ($index as $i => $v) {
                                if (is_numeric($i)) {
                                    $schema[$table_id][$key][$index_id]['fields'][] = $v;
                                    unset($schema[$table_id][$key][$index_id][$i]);
                                }
                            }
                        }
                    }
                } elseif (substr($key, 0, 1) != ':') {
                    if (isset($row[0])) {
                        $schema[$table_id][$key]['type'] = $row[0];
                        unset($schema[$table_id][$key][0]);
                    }
                    if (isset($row[1])) {
                        $schema[$table_id][$key]['params'] = $row[1];
                        unset($schema[$table_id][$key][1]);
                    }
                }
            }
        }
        return $schema;
    }
}
