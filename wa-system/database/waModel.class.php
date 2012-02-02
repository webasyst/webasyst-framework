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
    protected $writable   = true;

    /**
     * Name of the table
     * @var string
     */
    protected $table    = false;

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
    private $cache   = null;

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


    private $readonly   = false;

    /**
     * Конструктор
     *
     * @param string $type
     * @param string $bind
     * @param boolean $writeable
     */
    public function __construct($type = false, $writable = false)
    {
        $this->writable = $writable;
        $this->type     = $type ? $type : 'default';
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
                $this->fields = $this->describe(true);
            } else {
                $cache = new waSystemCache('db/'.$this->table);
                if (!($this->fields = $cache->get())) {
                    $this->fields = $this->describe(true);
                    $cache->set($this->fields);
                }
            }
            $runtime_cache->set($this->fields);
        }
        return $this->fields;
    }

    /**
     * Вовзращает результат запроса DESCRIBE $table
     *
     * @param $normalize
     * @return array
     */
    public function describe($normalize = true)
    {
        return $this->adapter->schema($this->table);
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
     * @param waDbCacher $cacher
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
            $cache->delete();
        }
        $this->cache_cleaners = array();
    }

    protected $sql;

    /**
     * Execute query
     * @param string $sql -
     * @return resource|boolean
     */
    private function run($sql, $unbuffer = false)
    {
        $sql = trim($sql);
        $result = $this->adapter->query($sql);

        if (!$result) {
            $error = "Query Error\nQuery: ".$sql.
                     "\nError: ".$this->adapter->errorCode() .
                     "\nMessage: ".$this->adapter->error();
            throw new waDbException($error, $this->adapter->errorCode());
        }

        return $result;
    }

    /**
     * Execute query
     * @param string $sql
     * @return resource|boolean
     */
    public function exec($sql, $params = null)
    {
        if (!$sql) {
            return true;
        }
        $waDbQueryAnalyzer = new waDbQueryAnalyzer($sql);
        switch($waDbQueryAnalyzer->getQueryType()) {
            case 'update': case 'replace': case 'delete': case 'insert': $this->cleanCache();
        }

        if ($params !== null) {
            return $this->prepare($sql)->exec($params);
        }

        return $this->run($sql);
    }

    /**
     * Выполняет запрос и возвращает иттератор результата
     *
     * @param string $sql
     * @return waDbResult|waDbResultSelect
     */
    public function query($sql, $params = null)
    {
        if ($params !== null) {
            return $this->prepare($sql)->query($params);
        }

        // Define type of the query
        $waDbQueryAnalyzer = new waDbQueryAnalyzer($sql);
        if ($this->cache && $this->cache instanceof waiCache && $waDbQueryAnalyzer->getQueryType() == 'select') {
            // if not cached
            if(!$this->cache->isCached()) {
                $result = $waDbQueryAnalyzer->invokeResult($this->run($sql), $this->adapter);
                // set cache
                $this->cache->set($result);
                $this->cache = null;

                return $result;
            }
            // Get from cache
            $cache  = $this->cache->get();
            if (!$cache instanceof waDbResultSelect) {
                $this->cache->delete();
            } else {
                $this->cache = null;
                return $cache;
            }
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
     * @param string $field
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
        $where = array();
        if (is_array($field)) {
            $w = $field;
            $return_object = $options;
            $options = $data;
            $data = $value;
            foreach ($w as $f => $v) {
                if (!isset($this->fields[$f])) {
                    throw new waException(sprintf('Unknown field %s', $f));
                }
                if (is_array($v)) {
                    $where[] = $this->escapeField($f)." IN (".implode("','", $this->escape($v))."')";
                } else {
                    $where[] = $this->escapeField($f)." = ".$this->getFieldValue($f, $v);
                }
            }
        } elseif (is_array($value)) {
            $where[] = $this->escapeField($field)." IN ('".implode("','", $this->escape($value))."')";
        } else {
            $where[] = $this->escapeField($field)." = ".$this->getFieldValue($field, $value);
        }

        $values = array();
        foreach ($data as $field => $value) {
            if (isset($this->fields[$field])) {
              $values[] = $this->escapeField($field)." = ".$this->getFieldValue($field, $value);
            }
        }

        if ($values && $where) {
           $sql = "UPDATE ".($options ? $options." " : "").$this->table. "
                   SET ".implode(", ", $values)."
                   WHERE ".implode(" AND ", $where);
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
              $values[] = $this->escapeField($field)." = ".$this->getFieldValue($field, $value);
            }
        }
        if ($values) {
           $sql = "REPLACE INTO ".$this->table. "
                   SET ".implode(", ", $values);
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
        if ($value === null && isset($this->fields[$field]['null']) && $this->fields[$field]['null']) {
            return 'NULL';
        }

        return $this->castValue($type, $value, isset($this->fields[$field]['null']) && $this->fields[$field]['null']);
    }

    /**
     * Приводит значение к нужному типу
     *
     * @param string $type
     * @param string $value
     * @return
     */
    protected function castValue($type, $value, $is_null = false)
    {
        switch ($type) {
            case 'bigint':
            case 'tinyint':
            case 'int':
                return (int)$value;
            case 'decimal':
            case 'double':
            case 'float':
                return str_replace(',', '.', (double)$value);
            case 'date':
                if (!$value) {
                    if ($is_null) {
                        return 'NULL';
                    } else {
                        return "'0000-00-00'";
                    }
                }
            case 'datetime':
                if (!$value) {
                    if ($is_null) {
                        return 'NULL';
                    } else {
                        return "'0000-00-00 00:00:00'";
                    }
                }
            case 'varchar':
            case 'text':
            default:
                return "'".$this->escape($value)."'";
        }
    }

    /**
     * Вставляет новую запись в таблицу
     *
     * @param array $data field => value
     * @param int $type pass 1 (or true) for `ON DUPLICATE KEY UPDATE`; pass 2 for `INSERT IGNORE`.
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
           $sql = "INSERT ".($type === 2 ? "IGNORE ":"")." INTO ".$this->table. "
                   (".implode(", ", array_keys($values)).") VALUES (".implode(", ", $values).")";
           if ($type === 1 || $type === true) {
               $sql .= " ON DUPLICATE KEY UPDATE ";
               unset($values[$this->id]);
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

    public function isAutoIncrement()
    {
        if ($this->id && !is_array($this->id) && isset($this->fields[$this->id])) {
            return $this->fields[$this->id]['extra'] == 'auto_increment';
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
     * @return string
     */
    public function escape($data, $type = null)
    {
        if (is_array($data)){
            foreach($data as $key => $value){
                if ($type === 'int') {
                    $data[$key] = (int)$value;
                } elseif ($type === 'like') {
                    $data[$key] = str_replace(array('%', '_'), array('\%', '\_'), $this->adapter->escape($value));
                } else {
                    $data[$key] = $this->adapter->escape($value);
                }
            }
            return $data;
        }
        switch ($type) {
            case 'int':
                return (int)$data;
            case 'like':
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
     * Возвращает конструктор запроса
     *
     * @return waDbQueryConstructor
     */
    public function getQueryConstructor()
    {
        return new waDbQueryConstructor($this);
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
        return $this->getByField($this->id, $value);
    }

    public function getByField($field, $value = null, $all = false, $limit = false)
    {
        if (is_array($field)) {
            $limit = $all;
            $all = $value;
        }
        $sql = "SELECT * FROM ".$this->table;
        if ($where = $this->getWhereByField($field, $value)) {
            $sql .= " WHERE ".$where;
        }
        if ($limit) {
            $sql .= " LIMIT ".(int)$limit; 
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
     * @param string $field
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
            foreach ($field as $f => $v) {
                if (!isset($this->fields[$f])) {
                    throw new waException(sprintf(_ws('Unknown field %s'), $f));
                }
                if (is_array($v)) {
                    $where[] = ($add_table_name ? $this->table."." : "") . $f . " IN ('".implode("','", $this->escape($v))."')";
                } else {
                    $where[] = ($add_table_name ? $this->table."." : "") . $f . " = ".$this->getFieldValue($f, $v);
                }
            }
        } elseif (is_array($value)) {
            $where[] = ($add_table_name ? $this->table."." : "") . $field . " IN ('".implode("','", $this->escape($value))."')";
        } else {
            $where[] = ($add_table_name ? $this->table."." : "") . $field . " = ".$this->getFieldValue($field, $value);
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
     * Удаляет записи из таблицы по полю
     *
     * @param string|array $field
     * @param int|float|string|array $value
     * @return bool
     */
    public function deleteByField($field, $value = null)
    {
        $sql = "DELETE FROM ".$this->table;
        if ($where = $this->getWhereByField($field, $value)) {
            $sql .= " WHERE ".$where;
        }
        return $this->exec($sql);
    }


    /**
     * Подготавливает запрос и возвращает объект waDbStatement
     *
     * @param string $sql - запрос
     * @return waDbStatement
     */
    public function prepare($sql)
    {
        return new waDbStatement($this, $sql);
    }


    /**
     * Проверяет существует ли поле в таблице
     *
     * @param string $field
     * @return bool
     */
    public function fieldExists($field)
    {
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
}

