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
class waDbMySQLAdapter extends waDbAdapter
{
    const RESULT_ASSOC = MYSQL_ASSOC;
    const RESULT_NUM = MYSQL_NUM;
    const RESULT_BOTH = MYSQL_BOTH;
        
    public function connect($settings)
    {
        $host = $settings['host'].(isset($settings['port']) ? ':'.$settings['port'] : '');
        // Persistent connection
        if (isset($settings['persistent']) && $settings['persistent']) {
            $handler = @mysql_pconnect($host, $settings['user'], $settings['password'], true);
        } else {
            $handler = @mysql_connect($host, $settings['user'], $settings['password'], true);
        }
        if (!$handler) {
            throw new waDbException(mysql_error(), mysql_errno());
        }
        if (!mysql_select_db($settings['database'], $handler)) {
            throw new waDbException(mysql_error(), mysql_errno());
        }
        
        $charset = isset($settings['charset']) ? $settings['charset'] : 'utf8';
        @mysql_set_charset ($charset, $handler);
        if (isset($settings['sql_mode'])) {
            $sql = "SET SESSION sql_mode = '".mysql_real_escape_string($settings['sql_mode'], $handler)."'";
            @mysql_query($sql, $handler);
        }
        return $handler;
    }

    public function select_db($database)
    {
        return mysql_select_db($database, $this->handler);
    }
    
    public function query($query)
    {
        $r = mysql_query($query, $this->handler);
        // check error MySQL server has gone away
        if (!$r && mysql_errno($this->handler) == 2006 && mysql_ping($this->handler)) {
            return mysql_query($query, $this->handler);
        }
        return $r;
    }
    
    public function free($result)
    {
        return mysql_free_result($result);
    }
    
    public function data_seek($result, $offset)
    {
        return mysql_data_seek($result, $offset);
    }        
    
    public function close()
    {
        return mysql_close($this->handler);
    }
    
    public function num_rows($result)
    {
        return mysql_num_rows($result);
    }
    
    public function fetch_array($result, $mode = self::RESULT_NUM)
    {
        return mysql_fetch_array($result, $mode);
    }
    
    public function fetch_assoc($result)
    {
        return mysql_fetch_assoc($result);
    }    
    
    public function insert_id()
    {
        return mysql_insert_id($this->handler);
    }
    
    public function affected_rows()
    {
        return mysql_affected_rows($this->handler);
    }        
    
    public function escape($string)
    {
        return mysql_real_escape_string($string, $this->handler);
    }    
    
    public function error()
    {
        return mysql_error($this->handler);
    }
    
    public function ping()
    {
        return mysql_ping($this->handler);
    }
    
    public function errorCode()
    {
        return mysql_errno($this->handler);
    }

    public function schema($table, $keys = false)
    {
        $res = $this->query("DESCRIBE ".$table);
        if (!$res) {
            $this->exception();
        }
        $result = array();
        while ($row = $this->fetch_assoc($res)) {
            $field = array();
            $i = strpos($row['Type'], '(');
            if ($i === false) {
                $field['type'] = $row['Type'];
                $field['params'] = null;
            } else {
                $field['type'] = substr($row['Type'], 0, $i);
                $field['params'] = substr($row['Type'], $i + 1, strpos($row['Type'], ')') - $i - 1);
                if (strpos($row['Type'], ')') != strlen($row['Type']) - 1) {
                    $field[trim(substr($row['Type'], strpos($row['Type'], ')') + 1))] = 1;
                }
            }
            if ($row['Null'] != 'YES') {
                $field['null'] = 0;
            }

            if ($row['Default'] !== null) {
                $field['default'] = $row['Default'];
            }

            if ($row['Extra'] == 'auto_increment') {
                $field['autoincrement'] = 1;
            }
            $result[$row['Field']] = $field;
        }
        if ($keys) {
            $res = $this->query("SHOW INDEX FROM ".$table);
            if (!$res) {
                $this->exception();
            }
            $rows = array();
            while ($row = $this->fetch_assoc($res)) {
                if ($row['Sub_part']) {
                    $f = array($row['Column_name'], $row['Sub_part']);
                } else {
                    $f = $row['Column_name'];
                }
                if (isset($rows[$row['Key_name']])) {
                    $rows[$row['Key_name']]['fields'][] = $f;
                } else {
                    $rows[$row['Key_name']] = array(
                        'fields' => array($f)
                    );
                    if ($row['Key_name'] != 'PRIMARY' && !$row['Non_unique']) {
                        $rows[$row['Key_name']]['unique'] = 1;
                    }
                }
            }
            $result[':keys'] = $rows;
        }
        return $result;
    }

    public function createTable($table, $data)
    {
        $fields = array();
        foreach ($data as $field_id => $field) {
            if (substr($field_id, 0, 1) != ':') {
                $type = $field['type'].(!empty($field['params']) ? '('.$field['params'].')' : '');
                if (isset($field['null']) && !$field['null']) {
                    $type .= ' NOT NULL';
                }
                if (isset($field['default'])) {
                    if ($field['default'] == 'CURRENT_TIMESTAMP') {
                        $type .= " DEFAULT ".$field['default'];
                    } else {
                        $type .= " DEFAULT '".$field['default']."'";
                    }
                }
                if (!empty($field['autoincrement'])) {
                    $type .= ' AUTO_INCREMENT';
                }
                $fields[] = $this->escapeField($field_id)." ".$type;
            }
        }
        $keys = array();
        foreach ($data[':keys'] as $key_id => $key) {
            if ($key_id == 'PRIMARY') {
                $k = "PRIMARY KEY";
            } else {
                $index_type = '';
                foreach (array('unique', 'fulltext', 'spatial') as $tk) {
                    if (!empty($key[$tk])) {
                        $index_type = strtoupper($tk).' ';
                        break;
                    }
                }
                $k = $index_type."KEY ".$this->escapeField($key_id);
            }
            $key_fields = array();
            foreach ($key['fields'] as $f) {
                if (is_array($f)) {
                    $key_fields[] = $this->escapeField($f[0])." (".$f[1].")";
                } else {
                    $key_fields[] = $this->escapeField($f);
                }
            }
            $keys[] = $k." (".implode(', ', $key_fields).')';
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".$table." (".implode(",\n", $fields);
        if ($keys) {
            $sql .= ", ".implode(",\n", $keys);
        }
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
        if (!$this->query($sql)) {
            $this->exception();
        }
    }

    protected function exception()
    {
        throw new waDbException(mysql_error($this->handler), mysql_errno($this->handler));
    }
    
}