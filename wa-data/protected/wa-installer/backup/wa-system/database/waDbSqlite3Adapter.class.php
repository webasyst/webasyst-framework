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
class waDbSqlite3Adapter extends waDbAdapter
{

    const RESULT_ASSOC = SQLITE3_ASSOC;
    const RESULT_NUM = SQLITE3_NUM;
    const RESULT_BOTH = SQLITE3_BOTH;

    /**
     * @var SQLite3
     */
    protected $handler;

    public function connect($settings)
    {
        return new SQLite3($settings['database']);
    }

    public function query($query)
    {
        //echo $query;
        return @$this->handler->query($query);
    }

    public function close()
    {
        return $this->handler->close();
    }

    /**
     * @param SQLite3Result $result
     * @return mixed
     */
    public function free($result)
    {
        return $result->finalize();
    }

    /**
     * @param SQLite3Result $result
     * @param $offset
     * @return bool
     */
    public function data_seek($result, $offset)
    {
        $result->reset();
        while ($offset--) {
            $result->fetchArray();
        }
        return true;
    }

    /**
     * @param SQLite3Result $result
     * @return int
     */
    public function num_rows($result)
    {
         $num_rows = 0;
          while($result->fetchArray()) {
            $num_rows++;
          }
          $result->reset();
          return $num_rows;
    }

    /**
     * @param SQLite3Result $result
     * @param int $mode
     * @return mixed
     */
    public function fetch_array($result, $mode = self::RESULT_NUM)
    {
        return $result->fetchArray($mode);
    }

    public function insert_id()
    {
        return $this->handler->lastInsertRowID();
    }

    public function affected_rows()
    {
        return $this->handler->changes();
    }

    public function escape($string)
    {
        return $this->handler->escapeString($string);
    }

    public function error()
    {
        return $this->handler->lastErrorMsg();
    }

    public function errorCode()
    {
        return $this->handler->lastErrorCode();
    }

    public function multipleInsert($table, $fields, $values)
    {
        $sql = "INSERT INTO ".$table." (".implode(',', $fields).") SELECT ".implode(' UNION SELECT ', $values);
        return $this->query($sql);
    }


    public function schema($table, $keys = false)
    {
        $res = $this->handler->query("SELECT * FROM sqlite_master WHERE name = '".$table."'");
        $row = $res->fetchArray();
        if (!$row) {
            return array();
        }

        $sql = $row['sql'];
        preg_match("/\((.*)\)/is", $sql, $match);
        $fields = explode(",", $match[1]);

        $result = array();
        foreach ($fields as $f) {
            $m = explode(" ", trim($f), 3);
            if ($m[0] == 'PRIMARY' && $m[1] == 'KEY') break;
            $field = array(
                'type' => strtolower($m[1]),
                'extra' => isset($m[2]) ? $m[2] : ''
            );
            if (strpos($field['extra'], 'NOT NULL') !== false) {
                $field['null'] = 0;
            } else {
                $field['null'] = 1;
            }
            if (strpos($field['extra'], 'AUTOINCREMENT') !== false) {
                $field['autoincrement'] = 1;
            }
            $i = strpos($field['type'], '(');
            if ($i === false) {
               $field['length'] = null;
            } else {
               $field['type'] = substr($field['type'], 0, $i);
               $field['length'] = substr($field['type'], $i + 1, -1);
            }
            if ($field['type'] == 'integer') {
               $field['type'] = 'int';
            }
            $result[$m[0]] = $field;
        }
        return $result;
    }

    public function createTable($table, $data)
    {
        $fields = array();
        foreach ($data as $field_id => $field) {
            if (substr($field_id, 0, 1) != ':') {
                if ($field['type'] == 'enum') {
                    $vars = explode(', ', $field['params']);
                    $n = 0;
                    foreach ($vars as $v) {
                        $l = strlen(trim($v)) - 2;
                        if ($l > $n) {
                            $n = $l;
                        }
                    }
                    $type = 'string('.$n.')';
                } else {
                    $type = $field['type'].(!empty($field['params']) ? '('.$field['params'].')' : '');
                }
                if (isset($field['null']) && !$field['null']) {
                    $type .= ' NOT NULL';
                }
                if (isset($field['default'])) {
                    $type .= " DEFAULT '".$field['default']."'";
                }
                if (!empty($field['autoincrement'])) {
                    $type = 'INTEGER PRIMARY KEY AUTOINCREMENT';
                    unset($data[':keys']['PRIMARY']);
                }
                $fields[] = $field_id." ".$type;
            }
        }
        $keys = array();
        $indexes = array();
        foreach ($data[':keys'] as $key_id => $key) {
            if ($key_id == 'PRIMARY') {
                $keys[] = "PRIMARY KEY (".implode(', ', $key['fields']).')';
            } else {
                $indexes[] = array(
                    'table' => $table,
                    'name' => $key_id,
                    'fields' => $key['fields'],
                    'unique' => isset($key['unique']) ? $key['unique'] : 0
                );
            }
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".$table." (".implode(",\n", $fields);
        if ($keys) {
            $sql .= ", ".implode(",\n", $keys);
        }
        $sql .= ")";
        $this->query($sql);


        // create indexes
        foreach ($indexes as $index) {
            $this->createIndex($index['table'], $index['name'], $index['fields'], $index['unique']);
        }
    }

    public function createIndex($table, $name, $fields, $unique = false)
    {
        $sql = "CREATE ".($unique ? 'UNIQUE ' : '')."INDEX IF NOT EXISTS ".$table.'_'.$name.' ON '.$table.'('.implode(', ', $fields).')';;
        $this->query($sql);
    }

}