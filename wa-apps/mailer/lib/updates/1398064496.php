<?php
$mod = new waModel();

$mod->exec("CREATE TABLE IF NOT EXISTS mailer_subscriber_temp (
                id INT(11) NOT NULL AUTO_INCREMENT,
                hash VARCHAR(100) NOT NULL,
                data TEXT NOT NULL,
                create_datetime DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX hash (hash ASC))
            ENGINE = MyISAM
            DEFAULT CHARACTER SET = utf8");