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
        @mysql_query ("SET NAMES '" . $charset . "' COLLATE '".$charset."_bin'", $handler);
        if (isset($settings['sql_mode'])) {
            $sql = "SET SESSION sql_mode = '".mysql_real_escape_string($settings['sql_mode'], $handler)."'";
            @mysql_query($sql, $handler);
        }
        return $handler;
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
    
    public function schema($table)
    {
        $res = $this->query("DESCRIBE ".$table);
        if (!$res) {
            $this->exception();
        } 
           $result = array();
           while ($row = mysql_fetch_assoc($res)) {
               $field = array();
               $i = strpos($row['Type'], '(');
               if ($i === false) {
                   $field['type'] = $row['Type'];
                   $field['length'] = null;
               } else {
                   $field['type'] = substr($row['Type'], 0, $i);
                   $field['length'] = substr($row['Type'], $i + 1, -1);
               }
               $field['null'] = $row['Null'] == 'YES' ? 1 : 0;
               $field['default'] = $row['Default'] === 'NULL' ? ($field['null'] ? null : '') : $row['Default'];
               $field['extra'] = $row['Extra']; 
               $result[$row['Field']] = $field;
           }
           return $result;        
    }
    
    protected function exception()
    {
        throw new waDbException(mysql_error($this->handler), mysql_errno($this->handler));
    }
    
}