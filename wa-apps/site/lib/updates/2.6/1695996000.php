<?php

/**
 * 2023-09-29 17:00:00 GMT+0300
 */

try {
    $sbm = new siteBlockModel();
    $sbm->query("ALTER TABLE site_block MODIFY COLUMN content MEDIUMTEXT NOT NULL");
} catch (Exception $e) {

}

