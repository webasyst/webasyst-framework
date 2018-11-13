<?php

class webasystInstaller
{
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
        $this->setVerificationChannelForDomainConfigs($channel['id']);
        $this->setVerificationChannelForBackendConfig($channel['id']);
    }

    protected function setVerificationChannelForDomainConfigs($channel_id)
    {
        $domain_configs = wa()->getConfig()->getAuth();
        if (!is_array($domain_configs) || !$domain_configs) {
            return;
        }

        // work through all domain auth configs and 'choose' that default channel
        $need_to_set = false;
        foreach ($domain_configs as $domain => &$config) {
            $changed = $this->setVerificationChannelForAuthConfig($config, $channel_id);
            if ($changed && isset($config['params']) && is_array($config['params'])) {
                $config['signup_confirm'] = !empty($config['params']['confirm_email']);
            }
            $need_to_set = $need_to_set || $changed;
        }
        unset($config);

        if ($need_to_set) {
            wa()->getConfig()->setAuth($domain_configs);
        }
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
        $tables = array_map('strval', (array)$table);
        if (empty($tables)) {
            return;
        }

        $db_path = wa()->getAppPath('lib/config/db.php', 'webasyst');
        $db = include($db_path);

        $db_partial = array();
        foreach ($tables as $table) {
            if (isset($db[$table])) {
                $db_partial[$table] = $db[$table];
            }
        }

        if (empty($db_partial)) {
            return;
        }

        $m = new waModel();
        $m->createSchema($db_partial);
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
}
