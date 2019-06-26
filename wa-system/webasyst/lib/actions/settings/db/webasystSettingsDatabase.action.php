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

    public function execute()
    {
        $mysql_version = $this->getMysqlVersion();
        $db_name = $this->getDbName();
        $mb4_is_supported = $this->mb4IsSupported();

        $is_debug = (bool) waSystemConfig::isDebug();

        $this->view->assign(array(
            'mysql_version'    => $mysql_version,
            'db_name'          => $db_name,
            'mb4_is_supported' => $mb4_is_supported,
            'is_debug'         => $is_debug,
        ));
    }

    protected function getMysqlVersion()
    {
        if (!$this->mysql_version) {
            $version = $this->getModel()->query('SELECT VERSION()')->fetch();
            $version = !empty($version[0]) ? $version[0] : null;
            $this->mysql_version = $version;
        }
        return $this->mysql_version;
    }

    protected function getDbName()
    {
        if (!$this->db_name) {
            $name = $this->getModel()->query('SELECT DATABASE()')->fetch();
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