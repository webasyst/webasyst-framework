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
        @$handler->query("SET NAMES '" . $charset . "' COLLATE '".$charset."_bin'");
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
        $types = array(
            'tinyint' => 'integer',
            'smallint' => 'integer',
            'int' => 'integer',
            'varchar' => 'string',
            'mediumtext' => 'text',
            'bigtext' => 'text'
        );
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
            if (isset($types[$field['type']])) {
                $field['type'] = $types[$field['type']];
            }
            $field['null'] = $row['Null'] == 'YES' ? 1 : 0;
            $field['default'] = $row['Default'] === 'NULL' ? null : $row['Default'];
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
                if (isset($rows[$row['Key_name']])) {
                    $rows[$row['Key_name']]['fields'][] = $row['Column_name'];
                } else {
                    $rows[$row['Key_name']] = array(
                        'fields' => array($row['Column_name']),
                        'unique' => $row['Non_unique'] ? 0 : 1
                    );
                }
            }
            $result[':keys'] = $rows;
        }
        return $result;
    }
    
    protected function exception()
    {
        throw new waDbException($this->error(), $this->errorCode());
    }
    
}