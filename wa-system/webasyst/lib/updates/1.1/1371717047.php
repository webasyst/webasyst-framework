<?php

$model = new waModel();

// new table for api tokens
$model->exec('CREATE TABLE IF NOT EXISTS `wa_api_tokens` (
  `contact_id` int(11) NOT NULL,
  `client_id` varchar(32) NOT NULL,
  `token` varchar(32) NOT NULL,
  `scope` text NOT NULL,
  `create_datetime` datetime NOT NULL,
  `expires` datetime DEFAULT NULL,
  PRIMARY KEY (`token`),
  UNIQUE KEY `contact_client` (`contact_id`,`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8');

// new table for api auth codes
$model->exec('CREATE TABLE IF NOT EXISTS `wa_api_auth_codes` (
  `code` varchar(32) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `client_id` varchar(32) NOT NULL,
  `scope` text NOT NULL,
  `expires` datetime NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8');

try {
    // try move data from old table
    $rows = $model->query("SELECT * FROM wa_contact_tokens");
    $api_tokens_model = new waApiTokensModel();
    foreach ($rows as $row) {
        $row['create_datetime'] = $row['create_timestamp'];
        unset($row['expires']);
        $api_tokens_model->insert($row, 2);
    }
    // remove old table
    $model->exec("DROP TABLE wa_contact_tokens");
} catch (waDbException $e) {
}


// remove old files
$path = $this->getAppPath('lib/models/waContactTokens.model.php');
if (file_exists($path)) {
    waFiles::delete($path);
}

// create new file api.php in root path
$path = $this->getRootPath().'/api.php';
if (!file_exists($path)) {
    file_put_contents($path, "<?php
require_once(dirname(__FILE__).'/wa-system/api/api.php');
");
}
