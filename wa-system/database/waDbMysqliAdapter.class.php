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

    const MB4_SUPPORTED_VERSION = '5.5.3';

    /**
     * @var mysqli
     */
    protected $handler;

    private $charset;

    public function connect($settings)
    {
        $host = $settings['host'];
        $port = isset($settings['port']) ? $settings['port'] : ini_get("mysqli.default_port");
        $handler = @new mysqli($host, $settings['user'], $settings['password'], $settings['database'], $port);
        if ($handler->connect_error) {
            throw new waDbException($handler->connect_error, $handler->connect_errno);
        }

        $mysql_version = mysqli_get_server_info($handler);
        $mb4_is_supported = version_compare($mysql_version, self::MB4_SUPPORTED_VERSION, '>=');

        $this->charset = isset($settings['charset']) ? $settings['charset'] : 'utf8';
        if (!isset($settings['charset']) && $mb4_is_supported) {
            $this->charset = 'utf8mb4';
        }

        $charset_result = @$handler->set_charset($this->charset);
        if (!$charset_result) {
            $handler->set_charset('utf8'); // fallback
        }

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
        $r = @$this->handler->query($query);
        if (!$r) {
            switch ($this->handler->errno) {
                case 2006:
                    // check error MySQL server has gone away
                    $ping = $this->handler->ping();
                    if (!$ping && $this->settings) {
                        $this->close();
                        sleep(1);
                        $this->handler = $this->connect($this->settings);
                        $ping = $this->handler->ping();
                    }
                    $r = $ping ? @$this->handler->query($query) : false;
                    break;
                case 1104:
                    // try set sql_big_selects
                    $this->handler->query('SET SQL_BIG_SELECTS=1');
                    $r = @$this->handler->query($query);
                    break;
            }
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

    /**
     * @param string $table
     * @param bool $keys
     * @return array
     * @throws waDbException
     */
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
        $statements = $this->buildStatements($data);

        $fields = $statements['fields'];
        $keys   = $statements['keys'];

        $sql = "CREATE TABLE IF NOT EXISTS ".$table." (".implode(",\n", $fields);
        if ($keys) {
            $sql .= ", ".implode(",\n", $keys);
        }

        $sql .= ") ";

        #setup engine
        $engine = ifset($data, ':options', 'engine', 'MyISAM');
        $engine = $this->engineIsAllowed($engine);
        $sql .= " ENGINE={$engine}";

        #setup charset
        $charset = ifset($data, ':options', 'charset', 'utf8');
        if ($this->charsetIsAllowed($charset)) {
            $sql .= " DEFAULT CHARSET={$charset}";
        } else {
            $sql .= " DEFAULT CHARSET=utf8";
        }

        if (!$this->query($sql)) {
            $this->exception();
        }
    }

    /**
     * Add column by db.php schema for current table
     *
     * @param string      $table
     * @param string      $column
     *
     * @param string      $table_schema - db.php config TABLE schema
     *                                  See db.php format
     *
     * @param null|string $after_column
     *
     * @param bool        $emulate
     * @return string|false
     * @throws waDbException
     */
    public function addColumn($table, $column, $table_schema, $after_column = null, $emulate = false)
    {
        $statements = $this->buildStatements($table_schema);
        $fields = $statements['fields'];

        if (!isset($fields[$column])) {
            return;
        }

        if ($this->query("SELECT `{$column}` FROM `{$table}` WHERE 0")) {
            return; // column exist - skip
        }

        $statement = $fields[$column];

        $sql = "ALTER TABLE `{$table}` ADD COLUMN {$statement}";

        if ($after_column && isset($fields[$after_column])) {
            $sql .= " AFTER `{$after_column}`";
        }

        if ($emulate) {
            return $sql;
        } elseif (!$this->query($sql)) {
            $this->exception();
        } else {
            return $sql;
        }
    }

    /**
     * Modify column by db.php schema for current table
     *
     * @param string      $table
     * @param string      $column
     *
     * @param string      $table_schema - db.php config TABLE schema
     *                                  See db.php format
     *
     * @param null|string $after_column
     *
     * @param bool        $emulate
     * @return string|false
     * @throws waDbException
     */
    public function modifyColumn($table, $column, $table_schema, $after_column = null, $emulate = false)
    {
        $statements = $this->buildStatements($table_schema);
        $fields = $statements['fields'];

        if (!isset($fields[$column])) {
            return;
        }

        if (!$this->query("SELECT `{$column}` FROM `{$table}` WHERE 0")) {
            return $this->addColumn($table, $column, $table_schema, $after_column, $emulate);
        } else {

            $statement = $fields[$column];

            $field = $table_schema[$column];

            $sqls = array();

            if (isset($field['null']) && !$field['null']) {
                $default = null;
                if (isset($field['default'])) {
                    if ($field['default'] == 'CURRENT_TIMESTAMP') {
                        $default = 'NOW()';
                    } else {
                        $default = "'".$field['default']."'";
                    }
                    $sqls['update'] = "UPDATE `{$table}` SET `{$column}` = {$default} WHERE `{$column}` IS NULL";
                } elseif (in_array(strtolower($field['type']), array('datetime'), true)) {
                    //Handle incorrect datetime value: '0000-00-00 00:00:00' for column at strict mode
                    $default = 'NOW()';
                    $sqls['update'] = "UPDATE `{$table}` SET `{$column}` = {$default} WHERE (`{$column}` IS NULL) OR (`{$column}` = '0000-00-00 00:00:00')";
                }
            }

            $sqls['alter'] = "ALTER TABLE `{$table}` MODIFY COLUMN {$statement}";

            if ($after_column && isset($fields[$after_column])) {
                $sqls['alter'] .= " AFTER `{$after_column}`";
            }

            if (!$emulate) {
                foreach ($sqls as $sql) {
                    if (!$this->query($sql)) {
                        $this->exception();
                    }
                }
            }

            return implode(";\n", $sqls);
        }
    }

    protected function buildStatements($data)
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

                if (isset($field['charset'])) {
                    if ($this->charsetIsAllowed($field['charset'])) {
                        $type .= ' CHARACTER SET '.$field['charset'];
                    } else {
                        unset($field['collation']);
                    }
                }

                if (isset($field['collation'])) {
                    $type .= ' COLLATE '.$field['collation'];
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
                $fields[$field_id] = $this->escapeField($field_id)." ".$type;
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
        return array(
            'fields' => $fields,
            'keys' => $keys
        );
    }

    private function charsetIsAllowed($charset)
    {
        return (
            ($this->charset == $charset) // same as connection utf8 or utf8mb4
            || (strpos($this->charset, $charset) === 0) // part of
        );
    }

    private function engineIsAllowed($engine)
    {
        static $engines;
        if ($engines === null) {
            $engines = array();
            $result = $this->query('SHOW ENGINES');
            while ($row = $this->fetch_assoc($result)) {
                if (in_array($row['Support'], array('YES', 'DEFAULT'), true)) {
                    $engines[strtolower($row['Engine'])] = $row['Engine'];
                }
            }
        }

        return ifset($engines, strtolower($engine), 'MyISAM');
    }

    protected function exception()
    {
        throw new waDbException($this->error(), $this->errorCode());
    }

}
