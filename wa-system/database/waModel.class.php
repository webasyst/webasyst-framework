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
     */
    public function getMetadata()
    {
        if ($this->table && !$this->fields) {
            $runtime_cache = new waRuntimeCache('db/'.$this->table);
            if ($this->fields = $runtime_cache->get()) {
                return $this->fields;
            }
            if (SystemConfig::isDebug()) {
                $this->fields = $this->getFields();
            } else {
                $cache = new waSystemCache('db/'.$this->table);
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
     * @return array field_id => default value (if set) or empty string
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

    protected $sql;

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
            $error = "Query Error\nQuery: ".$sql.
                     "\nError: ".$this->adapter->errorCode().
                     "\nMessage: ".$this->adapter->error();
            $trace = debug_backtrace();
            $stack = "";
            $default = array('file' => 'n/a', 'line' => 'n/a');
            foreach ($trace as $i => $row) {
                $row = array_merge($row, $default);
                $stack .= $i.". ".$row['file'].":".$row['line']."\n".
                     (isset($row['class']) ? $row['class'] : '').
                     (isset($row['type']) ? $row['type'] : '').
                     $row['function']."()\n";
            }
            waLog::log($error."\nStack:\n".$stack, 'db.log');
            throw new waDbException($error, $this->adapter->errorCode());
        }

        return $result;
    }

    /**
     * Execute query
     * @param string $sql
     * @param mixed $params
     * @return resource|boolean
     */
    public function exec($sql, $params = null)
    {
        if (!$sql) {
            return true;
        }
        $waDbQueryAnalyzer = new waDbQueryAnalyzer($sql);
        switch ($waDbQueryAnalyzer->getQueryType()) {
            case 'update': case 'replace': case 'delete': case 'insert': $this->cleanCache();
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
     * Executes query and returns iterator of the result
     *
     * @param string $sql
     * @param mixed $params
     * @return waDbResultDelete|waDbResultInsert|waDbResultReplace|waDbResultSelect|waDbResultUpdate
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
     *
     * Update table by primary key
     *
     * @param string $id
     * @param array $data
     * @param string $options
     * @param bool $return_object
     *
     * @see method updateByField
     */
    public function updateById($id, $data, $options = null, $return_object = false)
    {
        return $this->updateByField($this->id, $id, $data, $options, $return_object);
    }

    /**
     * Update table by field/fields
     *
     * @param string|array $field
     * @param string $value
     * @param array $data
     * @param string $options
     * @param bool $return_object - return bool result or object of waDbResultUpdate
     *
     * @example
     * 1) Update by one field and one value
     * $this->updateByField('field', $value, $data, $options);
     *    $data - updated data
     *    field = $value - condition for update
     * 2) Update by one field and a few values
     * $this->updateByField('field', array($value1, $value2), $data, $options);
     * 3) Update by a few fields and values
     * $this->updateByField(array('field1' => $value1, 'field2' => $value2), $data, $options);
     *
     * @return bool|null|waDbResultUpdate - result of execution update query
     */
    public function updateByField($field, $value, $data = null, $options = null, $return_object = false)
    {
        if (is_array($field)) {
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

        if ($values && $where) {
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
            throw new waException(sprintf('Unknown field %s', $field));
        }
        if (!isset($this->fields[$field]['type'])) {
            throw new waException(sprintf('Incorrect metadata for field %s', $field));
        }
        $type = strtolower($this->fields[$field]['type']);
        if ($value === null && (!isset($this->fields[$field]['null']) || $this->fields[$field]['null'])) {
            return 'NULL';
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
        switch ($type) {
            case 'bigint':
            case 'tinyint':
            case 'int':
            case 'integer':
                return (int) $value;
            case 'decimal':
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
     * Inserts a new record to the table
     *
     * @param array $data field => value
     * @param int $type pass 1 (or true) for `ON DUPLICATE KEY UPDATE`; pass 2 for `INSERT IGNORE`.
     * @return bool|int|resource
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

    public function multipleInsert($data)
    {
        if (!$data) {
            return true;
        }
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
        if ($values) {
            return $this->adapter->multipleInsert($this->table, $fields, $values);
        }
        return true;
    }

    public function isAutoIncrement()
    {
        if ($this->id && !is_array($this->id) && isset($this->fields[$this->id])) {
            return !empty($this->fields[$this->id]['autoincrement']);
        }
        return false;
    }

    public function getAll($key = null, $normalize = false)
    {
        $sql = "SELECT * FROM ".$this->table;
        return $this->query($sql)->fetchAll($key, $normalize);
    }

    public function countAll()
    {
        return $this->query("SELECT COUNT(*) FROM ".$this->table)->fetchField();
    }

    /**
     * Escape data
     *
     * @param mixed $data
     * @param string $type - int|like
     * @return string
     */
    public function escape($data, $type = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($type === 'int') {
                    $data[$key] = (int) $value;
                } elseif ($type === 'like') {
                    $value = str_replace('\\', '\\\\', $value);
                    $data[$key] = str_replace(array('%', '_'), array('\%', '\_'), $this->adapter->escape($value));
                } else {
                    $data[$key] = $this->adapter->escape($value);
                }
            }
            return $data;
        }
        switch ($type) {
            case 'int':
                return (int) $data;
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
     * Returns name of the table
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table;
    }

    public function getTableId()
    {
        return $this->id;
    }

    public function getById($value)
    {
        return $this->getByField($this->id, $value, is_array($value) ? $this->id : false);
    }

    public function getByField($field, $value = null, $all = false, $limit = false)
    {
        if (is_array($field)) {
            $limit = $all;
            $all = $value;
            $value = false;
        }
        $sql = "SELECT * FROM ".$this->table;
        $where = $this->getWhereByField($field, $value);
        if ($where != '') {
            $sql .= " WHERE ".$where;
        }
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
     *
     * Returns WHERE condition for the SQL query
     *
     * @param string|array $field
     * @param string|array $value
     * @param bool $add_table_name - add or not table's name in the query
     * @throws waException
     * @return string
     */
    protected function getWhereByField($field, $value = null, $add_table_name = false)
    {
        $where = array();
        if (is_array($field)) {
            $add_table_name = $value;
            if ($add_table_name) {
                $prefix = is_string($add_table_name) ? $add_table_name."." : $this->table.".";
            } else {
                $prefix = "";
            }
            foreach ($field as $f => $v) {
                if (!isset($this->fields[$f])) {
                    throw new waException(sprintf(_ws('Unknown field %s'), $f));
                }
                if (is_array($v)) {
                    $where[] = $prefix.$this->escapeField($f)." IN ('".implode("','", $this->escape($v))."')";
                } else {
                    $where[] = $prefix.$this->escapeField($f).($v === null ? " IS NULL" : " = ".$this->getFieldValue($f, $v));
                }
            }
        } elseif (is_array($value)) {
            if ($value) {
                $where[] = ($add_table_name ? $this->table."." : "").$this->escapeField($field)." IN ('".implode("','", $this->escape($value))."')";
            } else {
                // analog field IN ('') - it's always false
                $where[] = '0'; // or false
                }
        } else {
            $where[] = ($add_table_name ? $this->table."." : "").$this->escapeField($field).
                       ($value === null ? " IS NULL" : " = ".$this->getFieldValue($field, $value));
        }
        return implode(" AND ", $where);
    }

    /**
     * Returns the number of rows in the table with the specified field
     *
     * @param string $field
     * @param string $value
     * @return int
     */
    public function countByField($field, $value = null)
    {
        $sql = "SELECT COUNT(*) FROM ".$this->table;
        if ($where = $this->getWhereByField($field, $value)) {
            $sql .= " WHERE ".$where;
        }
        return $this->query($sql)->fetchField();
    }

    /**
     * Delete records from table by primary key
     *
     * @param $value
     * @return bool
     */
    public function deleteById($value)
    {
        return $this->deleteByField($this->id, $value);
    }

    /**
     * Delete records from table by field
     *
     * @param string|array $field
     * @param int|float|string|array $value
     * @return bool
     */
    public function deleteByField($field, $value = null)
    {
        if ($where = $this->getWhereByField($field, $value)) {
            $sql = "DELETE FROM ".$this->table." WHERE ".$where;
            return $this->exec($sql);
        }
        return true;
    }

    /**
     * Prepare query and returns object of waDbStatement
     *
     * @param string $sql - запрос
     * @return waDbStatement
     */
    public function prepare($sql)
    {
        return new waDbStatement($this, $sql);
    }

    /**
     * Checks exists or not field in the table
     *
     * @param string $field
     * @return bool
     */
    public function fieldExists($field)
    {
        if (is_array($field)) {
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
     * @return waDbQuery
     */
    public function where($where)
    {
        $params = func_get_args();
        $where = array_shift($params);
        $query = new waDbQuery($this);
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
     * Checks whether or not the connection to the server is working.
     * If it has gone down, an automatic reconnection is attempted.
     *
     * @return bool
     */
    public function ping()
    {
        if (!$this->adapter->ping()) {
            // reconnect to the server
            $this->adapter->reconnect();
        }
        return true;
    }

    public function createSchema($schema)
    {
        $schema = $this->formatSchema($schema);
        $this->adapter->createSchema($schema);
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
