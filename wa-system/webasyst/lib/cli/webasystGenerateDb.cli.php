<?php

class webasystGenerateDbCli extends waCliController
{
    /**
     * @var waModel
     */
    protected $model;
    /**
     * @var array
     */
    private $params;


    private static $default = array(
        'view'   => array('all', 'changed'),
        'update' => array('all', 'none', ''),
        'ignore' => array('config', 'pattern',),
    );

    private function printHelp()
    {
        if (preg_match('/^webasyst(\w+)Cli$/', __CLASS__, $matches)) {
            $callback = create_function('$m', 'return strtolower($m[1]);');
            $action = preg_replace_callback('/^([\w]{1})/', $callback, $matches[1]);
        } else {
            $action = '';
        }
        $help = <<<HELP
Usage: php wa.php {$action} slug [parameters...] [tables...]

    Examine all tables of an app or plugin and update its lib/config/db.php file
    to match the database.

Slug examples:
    myapp
    someapp/myplugin

Optional parameters:
    [tables...]             Space separated list of table names to be updated.
                            Defaults to all tables starting with app or plugin prefix.
    -view all|changed       Output table structure to screen
    -update all|none        If set to none, db.php is not changed. Useful with -view
    -ignore config|pattern  Ignore plugin tables when updating db.php of an app.
                            config: ignore tables defined in db.php of plugins
                            pattern: ignore tables starting with plugin prefix
HELP;

        print($help)."\n";
    }

    private function printLegend()
    {
        $help = <<<HELP
REPORT LEGEND:
S: table status
    = - no changes
    * - table modified
    + - table added
    - - table missed or deleted
    I - table changes are ignored
TABLE: table name
CHANGES: text with details of table status
HELP;
        print(str_repeat('-', 120)."\n");
        print($help)."\n";

    }

    public function execute()
    {
        $tables = array();
        $this->params = waRequest::param();
        unset($this->params[0]);
        $invalid = array();
        if ($app_id = waRequest::param(0)) {
            if ($this->params) {
                foreach ($this->params as $k => $v) {
                    if (is_numeric($k)) {
                        unset($this->params[$k]);
                        $tables[] = $v;
                    } else {
                        if (!isset(self::$default[$k])) {
                            $invalid[] = sprintf("Unexpected param [ -%s]\n", $k);
                        } elseif (!in_array($v, self::$default[$k])) {
                            $invalid[] = sprintf("Unexpected param [ -%s] value, use one of this: %s\n", $k, implode(', ', array_filter(self::$default[$k])));
                        }
                    }
                }
            }

        }


        if (!$invalid && $app_id) {
            $this->model = new waModel();
            $sql = 'SELECT DATABASE()';
            $database = $this->model->query($sql)->fetchField();

            echo sprintf("Check tables for '%s' app(s) at %s database\nwith options:\n", $app_id, $database);
            foreach (self::$default as $field => $value) {
                echo sprintf("\t%-6s: %s\n", $field, $this->getParam($field));
            }

            $this->printLegend();
            if ($app_id == 'all') {
                $apps = wa()->getApps();
                foreach ($apps as $app_id => $app) {
                    $this->generateSchema($app_id);
                }
            } elseif ($app_id) {
                $this->generateSchema($app_id, $tables);
            }

        } else {
            if ($invalid) {
                echo implode("", $invalid);
            }
            $this->printHelp();
        }
    }

    protected function generateSchema($app_id, $tables = array())
    {
        $plugin_id = false;
        if (strpos($app_id, '/') !== false) {
            list($app_id, $plugin_id) = explode('/', $app_id, 2);
            $path = wa()->getConfig()->getAppsPath($app_id, 'plugins/'.$plugin_id.'/lib/config/db.php');
        } else {
            $path = wa()->getConfig()->getAppsPath($app_id, 'lib/config/db.php');
        }

        $exists_schema = array();
        if (file_exists($path)) {
            $schema = include($path);
            $exists_schema = $schema;
            $exists_tables = array_keys($schema);
        } else {
            $exists_tables = array();
        }

        $exclude_patterns = array();

        if (!in_array($this->getParam('update'), array('all', 'none'))) {
            if (!$tables) {
                $tables = $exists_tables;
            }
        } elseif ($tables) {
            if (!is_array($tables)) {
                $tables = array($tables);
            }
            //TODO add pattern support
        } else {

            $exclude = array();

            if ($app_id == 'webasyst') {
                $prefix = 'wa';
            } else {
                $prefix = $app_id;
                if ($plugin_id) {
                    $prefix .= '_'.$plugin_id;
                } else {

                    if (SystemConfig::isDebug()) {
                        $plugins = waFiles::listdir(wa()->getConfig()->getAppsPath($app_id, 'plugins/'));
                        foreach ($plugins as $_id => $plugin_id) {
                            if (!preg_match('@^[a-z][a-z0-9_]+$@', $plugin_id)) {
                                unset($plugins[$_id]);
                            }
                        }
                        $plugins = array_values($plugins);
                    } else {
                        $plugins = wa($app_id)->getConfig()->getPlugins();
                        $plugins = array_keys($plugins);
                    }

                    foreach ($plugins as $plugin_id) {
                        if ($this->getParam('ignore') == 'pattern') {
                            $plugin_prefix = $app_id.'_'.$plugin_id;
                            if (in_array($plugin_prefix, $exists_tables)) {
                                print sprintf("Warning: Plugin %s has conflicted table namespace\n", $plugin_id);
                            }
                            $exclude = array_merge($exclude, $this->getTables($plugin_prefix));
                        } elseif ($this->getParam('ignore') == 'config') {
                            $plugin_path = wa()->getConfig()->getAppsPath($app_id, 'plugins/'.$plugin_id.'/lib/config/db.php');
                            if (file_exists($plugin_path)) {
                                $exclude_tables = include($plugin_path);
                                if (is_array($exclude_tables)) {
                                    $exclude = array_merge($exclude, array_keys($exclude_tables));
                                }
                            }
                        }

                        $exclude_patterns[] = sprintf('@(^%1$s$|^%1$s_|_%1$s$)@', $plugin_id);
                    }
                }
            }

            $tables = $this->getTables($prefix);
            $tables = array_diff($tables, $exclude);
        }
        print(str_repeat('-', 120)."\n");
        echo sprintf("%s\n", $app_id);
        $schema = array();

        if ($exists_tables || $tables) {


            $max = max(array_map('strlen', array_merge($exists_tables, array_keys($tables), array('12345678'))));


            $format = "%s|%-{$max}s|%8s|%30s|%30s\n";


            print(str_repeat('-', 120)."\n");
            echo sprintf($format, 'S', 'TABLE', 'STATUS', 'FIELDS', 'KEYS');
            print(str_repeat('-', 120)."\n");
            foreach ($tables as $t) {

                try {
                    $schema[$t] = $this->model->describe($t, 1);

                    if (in_array($t, $exists_tables)) {
                        $compare = $this->compareTable($schema[$t], $exists_schema[$t], $exclude_patterns);
                    } else {
                        $compare = array(
                            's' => '+',
                            'c' => 'ADDED',
                        );
                    }
                } catch (waDbException $ex) {
                    $compare = array(
                        's' => '!',
                        'c' => 'ERROR: '.$ex->getMessage(),
                    );

                }
                if (($compare['s'] !== '=') || ($this->getParam('view') == 'all')) {
                    echo sprintf($format, $compare['s'], $t, $compare['c'], ifset($compare['r']['FIELDS']), ifset($compare['r']['KEYS']));
                }
            }

            foreach ($exists_tables as $t) {
                if (!isset($schema[$t])) {
                    $s = '-';
                    $c = 'DELETED';
                    echo sprintf($format, $s, $t, $c, '', '');
                }
            }
        } else {
            echo sprintf("There no tables for %s\n", $app_id);
        }
        print(str_repeat('-', 120)."\n");

        if ($schema && ($this->getParam('update') != 'none')) {
            echo sprintf("Schema saved for %s\n", $app_id);
            if ($exclude_patterns) {
                foreach ($schema as &$table_schema) {
                    $table_schema = $this->cleanupSchema($table_schema, $exclude_patterns);
                }
                unset($table_schema);
            }
            // save schema to lib/config/db.php of the app
            waUtils::varExportToFile($this->schemaToString($schema), $path, false);
        }
    }

    protected function getTables($prefix)
    {
        // @todo: use db adapter to get tables
        $tables = array();
        $prefix = $this->model->escape($prefix, 'l');

        $sql = "SHOW TABLES LIKE '{$prefix}'";
        $tables = array_merge($tables, $this->model->query($sql)->fetchAll(null, true));
        $sql = "SHOW TABLES LIKE '{$prefix}\\_%'";
        $tables = array_merge($tables, $this->model->query($sql)->fetchAll(null, true));
        return $tables;

    }

    protected function schemaToString($schema)
    {
        $result = "array(\n";
        foreach ($schema as $table_id => $table) {
            $result .= "    '".$table_id."' => array(\n";
            // table
            foreach ($table as $key => $row) {
                $result .= "        '".$key."' => array(";
                if (substr($key, 0, 1) == ':') {
                    $result .= "\n";
                    foreach ($row as $k => $v) {
                        $result .= "            '".$k."' => ";
                        if ($key == ':keys') {
                            if (count($v) == 1 && count($v['fields']) == 1) {
                                $result .= var_export($v['fields'][0], true);
                            } else {
                                $result .= 'array('.$this->arrayToString($v['fields'], true);
                                foreach ($v as $tk => $tv) {
                                    if ($tk != 'fields') {
                                        $result .= ", '".$tk."' => ".var_export($tv, true);
                                    }
                                }
                                $result .= ')';
                            }
                        } else {
                            if (is_array($v)) {
                                $result .= $this->arrayToString($v);
                            } else {
                                $result .= var_export($v, true);
                            }
                        }
                        $result .= ",\n";
                    }
                    $result .= "        ";
                } else {
                    $result .= "'".$row['type']."'";
                    if (isset($row['params'])) {
                        $result .= ', ';
                        if (is_numeric($row['params'])) {
                            $result .= $row['params'];
                        } else {
                            $result .= '"'.$row['params'].'"';
                        }
                    }
                    foreach ($row as $k => $v) {
                        if ($k != 'type' && $k != 'params') {
                            $result .= ", '".$k."' => ".var_export($v, true);
                        }
                    }
                }
                $result .= "),\n";
            }
            $result .= "    ),\n";
        }
        $result .= ")";
        return $result;
    }

    protected function arrayToString($a, $is_part = false)
    {

        $with_keys = false;
        $n = count($a);
        for ($i = 0, reset($a); $i < $n; $i++, next($a)) {
            if (key($a) !== $i) {
                $with_keys = true;
                break;
            }
        }
        $result = $is_part ? '' : 'array(';
        $i = 0;
        foreach ($a as $k => $v) {
            if ($with_keys) {
                $result .= "'".$k."' => ";
            }
            if (is_array($v)) {
                $result .= $this->arrayToString($v);
            } else {
                $result .= var_export($v, true);
            }
            if (++$i < $n) {
                $result .= ', ';
            }
        }
        $result .= $is_part ? '' : ')';
        return $result;
    }

    private function compareTable($current_schema, $config_schema, $exclude_patterns)
    {
        foreach ($current_schema[':keys'] as $field => &$data) {
            $data += (array)$data['fields'];
            unset($data['fields']);
            unset($data);
        }
        $current_primary = (array)ifset($current_schema[':keys'], array());
        $exists_primary = (array)ifset($config_schema[':keys'], array());


        $table_changes = array(
            'FIELDS' => $this->compareFields($current_schema, $config_schema, $exclude_patterns),
            'KEYS'   => $this->compareKeys($current_primary, (array)$exists_primary, $exclude_patterns),
        );

        $table_changes = array_filter($table_changes);

        if ($table_changes) {
            if ((count($table_changes) == 1) && !empty($changes['ignored'])) {
                $s = 'I';
                $c = 'IGNORED';
            } else {
                $s = '*';
                $c = 'CHANGED';
            }
            $r = array();
            foreach ($table_changes as $type => $changes) {
                $r[$type] = array();
                if (!empty($changes['added'])) {
                    $r[$type][] = '+('.implode(', ', $changes['added']).')';
                }
                if (!empty($changes['deleted'])) {
                    $r[$type][] = '-('.implode(', ', $changes['deleted']).')';
                }
                if (!empty($changes['changed'])) {
                    $r[$type][] = '*('.implode(', ', $changes['changed']).')';
                }
                if (!empty($changes['ignored'])) {
                    $r[$type][] = 'i('.implode(', ', $changes['ignored']).')';
                }
                $r[$type] = implode('; ', $r[$type]);
            }
        } else {
            $s = '=';
            $c = 'SAME';
        }
        return compact('changes', 's', 'c', 'r');
    }

    private function cleanupSchema($schema, $exclude_patterns)
    {
        if ($exclude_patterns) {
            foreach ($schema as $field => &$info) {
                if ($field == ':keys') {
                    foreach ($info as $key => $key_info) {
                        foreach ($key_info['fields'] as $key_field) {
                            if (is_array($key_field)) {
                                $key_field = reset($key_field);
                            }
                            foreach ($exclude_patterns as $pattern) {
                                if (preg_match($pattern, $key_field)) {
                                    unset($info[$key]);
                                    break 2;
                                }
                            }
                        }
                    }
                } else {
                    foreach ($exclude_patterns as $pattern) {
                        if (preg_match($pattern, $field)) {
                            unset($schema[$field]);
                            break;
                        }
                    }
                }
                unset($info);
            }
        }
        return $schema;
    }


    private function compareFields($exists, $config, $exclude_patterns)
    {
        $default_field = array(
            'null' => 1,
        );
        $changes = array(
            'ignored' => array(),
        );
        if ($exclude_patterns) {
            foreach ($exists as $field => $data) {
                foreach ($exclude_patterns as $pattern) {
                    if (preg_match($pattern, $field)) {
                        $changes['ignored'][] = $field;
                        unset($exists[$field]);
                        break;
                    }
                }
            }
        }
        $fields = array(
            'exists' => array_keys($exists),
            'config' => array_keys($config),
        );
        $changes += array(
            'added'   => array_diff($fields['exists'], $fields['config'], array(':keys')),
            'deleted' => array_diff($fields['config'], $fields['exists'], array(':keys')),
            'changed' => array(),
        );
        $changed = array_diff(array_unique(array_intersect($fields['config'], $fields['exists'])), array(':keys'));

        foreach ($changed as $field) {

            $config_field = array_filter($config[$field] + $default_field);
            $exists_field = array_filter($exists[$field] + $default_field);
            if (isset($config_field[0])) {
                $config_field['type'] = $config_field[0];
                unset($config_field[0]);
            }
            if (isset($config_field[1])) {
                $config_field['params'] = $config_field[1];
                unset($config_field[1]);
            }

            $params = array_unique(array_merge(array_keys($config_field), array_keys($exists_field)));
            $field_changes = array();
            foreach ($params as $param) {
                if (!is_int($param)) {
                    if (ifset($config_field[$param]) != ifset($exists_field[$param])) {
                        $field_changes[] = sprintf('%s: %s -> %s', $param, ifset($config_field[$param], '0'), ifset($exists_field[$param], '0'));

                    }
                }
            }

            if ($field_changes) {
                $changes['changed'][] = sprintf('%s [%s]', $field, implode(', ', $field_changes));
            }
        }
        return array_filter($changes);
    }

    private function compareKeys($exists, $config, $exclude_patterns)
    {
        $changes = array(
            'ignored' => array(),
        );
        if ($exclude_patterns) {
            foreach ($exists as $key => $fields) {
                foreach ($fields as $field) {
                    foreach ($exclude_patterns as $pattern) {
                        if (preg_match($pattern, is_array($field) ? reset($field) : $field)) {
                            $changes['ignored'][] = $key;
                            unset($exists[$key]);
                            break 2;
                        }
                    }
                }
            }
        }

        foreach ($exists as $key => &$fields) {
            foreach ($fields as &$field) {
                if (is_array($field)) {
                    $field = implode(':', $field);
                }
                unset($field);
            }
            unset($fields);
        }

        foreach ($config as $key => &$fields) {
            $fields = (array)$fields;
            foreach ($fields as &$field) {
                if (is_array($field)) {
                    $field = implode(':', $field);
                }
                unset($field);
            }
            unset($fields);
        }

        $keys = array(
            'exists' => array_keys($exists),
            'config' => array_keys($config),
        );

        $changes += array(
            'added'   => array_diff($keys['exists'], $keys['config']),
            'deleted' => array_diff($keys['config'], $keys['exists']),
            'changed' => array(),
        );

        foreach ($changes['added'] as $id => $added) {

        }
        $changed = array_unique(array_intersect($keys['config'], $keys['exists']));

        foreach ($changed as $key) {

            $config_fields = (array)ifset($config[$key], array());
            $exists_fields = (array)ifset($exists[$key], array());
            $params = array_unique(array_merge(array_keys($config_fields), array_keys($exists_fields)));

            $key_changes = array(
                'changed' => array(),
            );
            foreach ($params as $param) {
                if (!is_int($param)) {
                    if (ifset($config_fields[$param]) != ifset($exists_fields[$param])) {
                        $key_changes['changed'][$param] = sprintf('%s: %s -> %s', $param, ifset($config_fields[$param], '0'), ifset($exists_fields[$param], '0'));

                    }
                    unset($config_fields[$param]);
                    unset($exists_fields[$param]);
                }
            }

            $key_changes += array(
                'added'   => array_diff($exists_fields, $config_fields),
                'deleted' => array_diff($config_fields, $exists_fields),
            );
            if ($key_changes = array_filter($key_changes)) {
                $changes['changed'][$key] = sprintf(' %s:', $key);
                if (!empty($key_changes['added'])) {
                    $changes['changed'][$key] .= ' +('.implode(', ', $key_changes['added']).')';
                }
                if (!empty($key_changes['deleted'])) {
                    $changes['changed'][$key] .= ' -('.implode(', ', $key_changes['deleted']).')';
                }
                if (!empty($key_changes['changed'])) {
                    $changes['changed'][$key] .= ' *('.implode(', ', $key_changes['changed']).')';
                }
            }
        }
        return array_filter($changes);
    }

    /**
     * @param string $name
     * @return string
     */
    private function getParam($name)
    {
        $default = ifset(self::$default[$name], array());
        return ifset($this->params[$name], reset($default));
    }
}
