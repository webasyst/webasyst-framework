<?php

class webasystGenerateDbCli extends waCliController
{
    /**
     * @var waModel
     */
    protected $model;

    public function execute()
    {
        $this->model = new waModel();

        if ($app_id = waRequest::param(0)) {
            $params = waRequest::param();
            if (count($params) == 1) {
                $this->generateSchema($app_id);
            } else {
                array_shift($params);
                foreach ($params as $k => $v) {
                    if (!is_numeric($k)) {
                        unset($params[$k]);
                    }
                }
                $this->generateSchema($app_id, $params);
            }
        } else {
            $apps = wa()->getApps();
            foreach ($apps as $app_id => $app) {
                $this->generateSchema($app_id);
            }
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
        if (waRequest::param('update') !== null) {
            $schema = include($path);
            if (!$tables) {
                $tables = array_keys($schema);
            }
        } elseif ($tables) {
            if (!is_array($tables)) {
                $tables = array($tables);
            }
        } else {
            $prefix = $app_id == 'webasyst' ? 'wa' : $app_id;
            if ($plugin_id) {
                $prefix .= '_'.$plugin_id;
            }
            // @todo: use db adapter to get tables
            $sql = "SHOW TABLES LIKE '".$prefix."\_%'";
            $tables = $this->model->query($sql)->fetchAll(null, true);
            $sql = "SHOW TABLES LIKE '".$prefix."'";
            $tables = array_merge($tables, $this->model->query($sql)->fetchAll(null, true));
        }

        $schema = array();

        foreach ($tables as $t) {
            echo $t."\n";
            try {
                $schema[$t] = $this->model->describe($t, 1);
            } catch (waDbException $ex) {
                print "\tError: ".$ex->getMessage()."\n";
            }
        }

        if ($schema) {
            // save schema to lib/config/db.php of the app
            waUtils::varExportToFile($this->schemaToString($schema), $path, false);
        }
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

}
