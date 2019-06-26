<?php

class webasystSettingsDatabaseAction extends webasystSettingsViewAction
{
    const MYSQL_MB4_SUPPORTED_VERSION = '5.5.3';

    /**
     * @var waModel
     */
    protected $model;

    /**
     * @var null|string
     */
    protected $mysql_version;

    /**
     * @var null|string
     */
    protected $db_name;

    /**
     * @var null|string
     */
    protected $connection_charset;

    public function execute()
    {
        $mysql_version = $this->getMysqlVersion();
        $db_name = $this->getDbName();
        $mb4_is_supported = $this->mb4IsSupported();
        $connection_charset = $this->getConnectionCharset();

        if ($mb4_is_supported) {
            // Check connection charset
            if ($connection_charset !== 'utf8mb4') {
                $mb4_is_supported = false;
            }
        }

        $is_debug = (bool)waSystemConfig::isDebug();

        $this->view->assign(array(
            'mysql_version'      => $mysql_version,
            'db_name'            => $db_name,
            'mb4_is_supported'   => $mb4_is_supported,
            'connection_charset' => $connection_charset,
            'is_debug'           => $is_debug,
        ));
    }

    protected function getMysqlVersion()
    {
        if (!$this->mysql_version) {
            $version = $this->getModel()->query('SELECT VERSION()')->fetchRow();
            $version = !empty($version[0]) ? $version[0] : null;
            $this->mysql_version = $version;
        }
        return $this->mysql_version;
    }

    protected function getDbName()
    {
        if (!$this->db_name) {
            $name = $this->getModel()->query('SELECT DATABASE()')->fetchRow();
            $name = !empty($name[0]) ? $name[0] : null;
            $this->db_name = $name;
        }
        return $this->db_name;
    }

    /**
     * @return bool
     */
    protected function mb4IsSupported()
    {
        $mysql_version = $this->getMysqlVersion();
        return version_compare($mysql_version, self::MYSQL_MB4_SUPPORTED_VERSION, '>=');
    }

    protected function getConnectionCharset()
    {
        if (!$this->connection_charset) {
            $charset = $this->getModel()->query("SHOW VARIABLES LIKE 'character_set_connection'")->fetchAll();
            $charset = !empty($charset[0]['Value']) ? $charset[0]['Value'] : null;
            $this->connection_charset = $charset;
        }
        return $this->connection_charset;
    }

    protected function getTables()
    {
        $tables = $this->getModel()->query('SHOW TABLE STATUS')->fetchAll('Name');
        foreach ($tables as &$table) {
            $table['columns'] = $this->getModel()->query('SHOW FULL COLUMNS FROM '.$table['Name'])->fetchAll('Field');
        }
        unset($table);

        return $tables;
    }

    protected function getModel()
    {
        if (!$this->model) {
            $this->model = new waModel();
        }

        return $this->model;
    }
}