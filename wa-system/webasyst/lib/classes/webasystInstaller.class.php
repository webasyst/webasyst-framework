<?php

class webasystInstaller
{
    protected $db;

    public function installAll()
    {
        $this->createCliFile();
        $this->protectPrivateDirs();
        $this->populateTables();
        $this->installDefaultVerificationChannel();
    }

    public function createCliFile()
    {
        // Create cli.php if not included in distr already
        $path = wa()->getConfig()->getRootPath().'/cli.php';
        if(!file_exists($path)) {
            if($fp = fopen($path,'w')) {
                $content = <<<CLI
#!/usr/bin/php
<?php
require_once(dirname(__FILE__).'/wa-system/cli.php');

CLI;
                fwrite($fp,$content);
                fclose($fp);
            }
        }
    }

    public function protectPrivateDirs()
    {
        // Protect private dirs with .htaccess
        $paths = array('log','cache','config','installer');
        foreach ($paths as $path) {
            $path = waSystem::getInstance()->getConfig()->getPath($path);
            waFiles::protect($path);
        }

    }

    public function populateTables()
    {
        foreach(array('wa_country', 'wa_region') as $table) {
            $this->populateTable($table);
        }
    }

    public function addStatusForContactData()
    {
        $m = new waModel();
        try {
            $m->query('SELECT `status` FROM `wa_contact_data` WHERE 0');
        } catch (waDbException $e) {
            $m->exec("ALTER TABLE `wa_contact_data` ADD COLUMN `status` VARCHAR(255) NULL DEFAULT NULL");
        }
    }

    public function installDefaultVerificationChannel()
    {
        $vcm = new waVerificationChannelModel();
        $channel = $vcm->getDefaultSystemEmailChannel();
        $channel_id = isset($channel['id']) ? $channel['id'] : null;
        $this->setVerificationChannelForDomainConfigs($channel_id);
        $this->setVerificationChannelForBackendConfig($channel_id);
    }

    protected function setVerificationChannelForDomainConfigs($channel_id)
    {
        $domain_configs = wa()->getConfig()->getAuth();
        if (!is_array($domain_configs) || !$domain_configs) {
            return;
        }

        // work through all domain auth configs and 'choose' that default channel
        foreach ($domain_configs as $domain => &$config) {
            $this->setVerificationChannelForAuthConfig($config, $channel_id);
            $need_confirm_email = isset($config['params']) && is_array($config['params']) && !empty($config['params']['confirm_email']);
            $config['signup_confirm'] = $need_confirm_email;
        }
        unset($config);

        wa()->getConfig()->setAuth($domain_configs);
    }

    protected function setVerificationChannelForBackendConfig($channel_id)
    {
        $config = wa()->getConfig()->getBackendAuth();
        $config = is_array($config) ? $config : array();
        $changed = $this->setVerificationChannelForAuthConfig($config, $channel_id);
        if ($changed) {
            wa()->getConfig()->setBackendAuth($config);
        }
    }

    protected function setVerificationChannelForAuthConfig(&$config, $channel_id)
    {
        $vcm = new waVerificationChannelModel();

        $channel_ids =
            isset($config['verification_channel_ids']) &&
            is_array($config['verification_channel_ids']) ? $config['verification_channel_ids'] : array();

        $prev_channel_ids = $channel_ids;

        array_unshift($channel_ids, $channel_id);

        $channel_ids = waUtils::toIntArray($channel_ids);
        $channel_ids = waUtils::dropNotPositive($channel_ids);
        $channel_ids = array_unique($channel_ids);
        $channels = $vcm->getChannels($channel_ids);
        $channel_ids = waUtils::getFieldValues($channels, 'id');

        $changed = $prev_channel_ids !== $channel_ids;
        if (!$changed) {
            return false;
        }

        $config['verification_channel_ids'] = $channel_ids;
        if (empty($config['verification_channel_ids'])) {
            unset($config['verification_channel_ids']);
        }

        return true;
    }

    protected function populateTable($table)
    {
        $source_file_path = wa()->getAppPath("lib/config/{$table}.sql", 'webasyst');

        $sql = null;
        if (file_exists($source_file_path)) {
            $sql = @file_get_contents($source_file_path);
        }

        if (!$sql) {
            waLog::log("Not source data to populate {$table}: {$source_file_path}");
            return;
        }

        try {
            $m = new waModel();
            $m->exec($sql);
        } catch (Exception $e) {
            $error = "Unable to populate {$table}: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString();
            waLog::log($error);
        }
    }

    public function createTable($table)
    {
        $db_tables = $this->getDbTables($table);
        if (empty($db_tables)) {
            return;
        }

        $m = new waModel();
        $m->createSchema($db_tables);
    }

    protected function getDbTables($table)
    {
        $tables = array_map('strval', (array)$table);
        if (empty($tables)) {
            return array();
        }

        // cache for only one instance
        if ($this->db === null) {
            $db_path = wa('webasyst')->getAppPath('lib/config/db.php', 'webasyst');
            $this->db = include($db_path);
        }

        $db_partial = array();
        foreach ($tables as $table) {
            if (isset($this->db[$table])) {
                $db_partial[$table] = $this->db[$table];
            }
        }

        if (empty($db_partial)) {
            return array();
        }

        return $db_partial;
    }

    public function tableExists($table)
    {
        $m = new waModel();
        $exists = true;
        try {
            $m->query("SELECT * FROM `{$table}` WHERE 0");
        } catch (waDbException $e) {
            $exists = false;
        }
        return $exists;
    }

    public function addColumn($table, $column_name, $column_definition, $after_column = null)
    {
        $disable_exception_log = waConfig::get('disable_exception_log');
        waConfig::set('disable_exception_log', true);

        $m = new waModel();

        try {
            $m->query("SELECT `{$column_name}` FROM `{$table}` WHERE 0");
        } catch (waDbException $e) {
            waConfig::set('disable_exception_log', false);

            $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column_name}` {$column_definition}";
            if ($after_column) {
                $sql .= " AFTER {$after_column}";
            }
            $m->exec($sql);
        }

        waConfig::set('disable_exception_log', $disable_exception_log);
    }

    public function renameColumn($table, $old_column_name, $new_column_name, $column_definition)
    {
        $disable_exception_log = waConfig::get('disable_exception_log');
        waConfig::set('disable_exception_log', true);

        $m = new waModel();

        try {
            $m->query("SELECT `{$new_column_name}` FROM `{$table}` WHERE 0");
        } catch (waDbException $e) {
            waConfig::set('disable_exception_log', false);
            $sql = "ALTER TABLE `{$table}` CHANGE `{$old_column_name}` `{$new_column_name}` {$column_definition}";
            $m->exec($sql);
        }

        waConfig::set('disable_exception_log', $disable_exception_log);
    }

    public function changeColumn($table, $column_name, $column_definition)
    {
        $m = new waModel();
        $sql = "ALTER TABLE `{$table}` CHANGE `{$column_name}` `{$column_name}` {$column_definition}";
        $m->query($sql);
    }
}
