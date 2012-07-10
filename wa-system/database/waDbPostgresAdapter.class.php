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
class waDbPostgresAdapter extends waDbAdapter
{
    public function connect($settings)
    {
        $connection = "host=".$settings['host'];
        $connection.= " dbname=".$settings['database'];
        $connection.= " user=".$settings['user'];
        $connection.= " password=".$settings['password'];
        return pg_connect($connection);
    }

    public function query($query)
    {
        return pg_query($this->handler, $query);
    }

    public function close()
    {
        return pg_close($this->handler);
    }

    public function num_rows($result)
    {
        return pg_num_rows($result);
    }

    public function insert_id()
    {
        $r = $this->query("SELECT LASTVAL()");
        $row = $this->fetch($r);
        return $row[0];
    }

    public function affected_rows($result = null)
    {
        return pg_affected_rows($result);
    }

    public function data_seek($result, $offset)
    {
        return pg_result_seek($result, $offset);
    }

    public function free($result)
    {
        return pg_free_result($result);
    }

    public function fetch_array($result, $mode = self::RESULT_NUM)
    {
        return pg_fetch_array($result);
    }

    public function fetch_assoc($result)
    {
        return pg_fetch_assoc($result);
    }

    public function escape($string)
    {
        return pg_escape_string($this->handler, $string);
    }

    public function error()
    {
        return pg_last_error($this->handler);
    }

    public function errorCode()
    {
    }

    public function escapeField($string)
    {
        return $string;
    }

    public function schema($table, $keys = false)
    {
        $sql = "select * from INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$table."'";
        $res = pg_query($this->handler, $sql);
        $result = array();
        while ($row = pg_fetch_assoc($res)) {
            $type = $row['data_type'];
            if ($type == 'integer') {
                $type = 'int';
            }
            $result[$row['column_name']] = array(
                'type' => $type
            );
        }
        return $result;
    }

    public function createTable($table, $data)
    {

        $fields = array();
        foreach ($data as $field_id => $field) {
            if (substr($field_id, 0, 1) != ':') {
                if ($field['type'] == 'enum') {
                    $type = $table."_".$field_id;
                    $sql = "SELECT exists(select 1 from pg_type where typname='".$type."')";
                    $r = $this->fetch_array($this->query($sql));
                    if ($r[0] === 'f') {
                        $sql = "CREATE TYPE ".$type." AS ENUM(".$field['params'].")";
                        $this->query($sql);
                    }
                } elseif ($field['type'] == 'datetime') {
                    $type = 'TIMESTAMP';
                } elseif ($field['type'] == 'timestamp') {
                    $type = 'TIMESTAMP';
                    if (isset($field['default'])) {
                        unset($field['default']);
                    }
                } elseif ($field['type'] == 'int') {
                    $type = 'INTEGER';
                } elseif ($field['type'] == 'bigint') {
                    $type = 'BIGINT';
                } elseif ($field['type'] == 'tinyint') {
                    $type = 'SMALLINT';
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
                    $type = 'serial';
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
        $sql = "SELECT * FROM pg_indexes WHERE tablename = '".$table."' AND indexname = '".$table.'_'.$name."'";
        $q = $this->query($sql);
        if (!$this->num_rows($q)) {
            $sql = "CREATE ".($unique ? 'UNIQUE ' : '')."INDEX ".$table.'_'.$name.' ON '.$table.'('.implode(', ', $fields).')';;
            $this->query($sql);
        }
    }
}