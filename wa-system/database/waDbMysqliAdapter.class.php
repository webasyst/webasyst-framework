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
class waDbMysqliAdapter extends waDbAdapter
{
    
    const RESULT_ASSOC = 1;
    const RESULT_NUM = 2;
    const RESULT_BOTH = 3;

    /**
     * @var mysqli
     */
    protected $handler;
        
    public function connect($settings)
    {
        $host = $settings['host'];
        $port = isset($settings['port']) ? $settings['port'] : ini_get("mysqli.default_port");
        $handler = @new mysqli($host, $settings['user'], $settings['password'], $settings['database'], $port);
        if ($handler->connect_error) {
            throw new waDbException($handler->connect_error, $handler->connect_errno);
        }
        
        $charset = isset($settings['charset']) ? $settings['charset'] : 'utf8';
        @$handler->set_charset($charset);
        if (isset($settings['sql_mode'])) {
            $sql = "SET SESSION sql_mode = '".$handler->real_escape_string($settings['sql_mode'])."'";
            @$handler->query($sql);
        }
        return $handler;
    }


    public function select_db($database)
    {
        return $this->handler->select_db($database);
    }
    
    /**
     * Performs a query on the database and return a result object or false
     * 
     * @param string $query - SQL-query
     * @return mysqli_result
     */
    public function query($query)
    {
        $r =  $this->handler->query($query);
        // check error MySQL server has gone away
        if (!$r && $this->handler->errno == 2006 && $this->handler->ping()) {
            return $this->handler->query($query);
        } elseif (!$r && $this->handler->errno == 1104) {
            // try set sql_big_selects
            $this->handler->query('SET SQL_BIG_SELECTS=1');
            return $this->handler->query($query);
        }
        return $r;
    }
    
    public function close()
    {
        return $this->handler->close();
    }
    
    public function num_rows($result)
    {
        return $result->num_rows;
    }

    /**
     * @param mysqli_result $result
     * @return mixed
     */
    public function free($result)
    {
        return $result->free_result();
    }

    /**
     * @param mysqli_result $result
     * @param int $offset
     * @return mixed
     */
    public function data_seek($result, $offset)
    {
        return $result->data_seek($offset);
    }    

    /**
     * @param mysqli_result $result
     * @param int $mode
     * @return mixed
     */
    public function fetch_array($result, $mode = self::RESULT_NUM)
    {
        return $result->fetch_array($mode);
    }

    /**
     * @param mysqli_result $result
     * @return mixed
     */
    public function fetch_assoc($result)
    {
        return $result->fetch_assoc();
    }    
    
    public function insert_id()
    {
        return $this->handler->insert_id;
    }
    
    public function affected_rows()
    {
        return $this->handler->affected_rows;
    }    
    
    public function escape($string)
    {
        return $this->handler->real_escape_string($string);
    }

    public function ping()
    {
        if (!@$this->handler->ping()) {
            return $this->reconnect();
        }
        return true;
    }
    
    public function error()
    {
        return $this->handler->error;
    }
    
    public function errorCode()
    {
        return $this->handler->errno;
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
                    if ($row['Index_type'] == 'FULLTEXT') {
                        $rows[$row['Key_name']]['fulltext'] = 1;
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
                foreach (array('unsigned', 'zerofill') as $k) {
                    if (!empty($field[$k])) {
                        $type .= ' '.strtoupper($k);
                    }
                }
                if (isset($field['null']) && !$field['null']) {
                    $type .= ' NOT NULL';
                } elseif (in_array(strtolower($field['type']), array('timestamp'))) {
                    $type .= ' NULL';
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
        $this->query($sql);
        if (!$this->query($sql)) {
            $this->exception();
        }
    }

    protected function exception()
    {
        throw new waDbException($this->error(), $this->errorCode());
    }
    
}