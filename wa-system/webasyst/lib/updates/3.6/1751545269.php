<?php
$m = new waModel();

try {
    $m->query("SELECT 1 FROM wa_agreement_log LIMIT 0");
    $m->exec('DROP TABLE `wa_agreement_log`');
} catch (waDbException $e) {
}

try {
    $m->query("SELECT 1 FROM wa_agreement_log LIMIT 0");
} catch (waDbException $e) {
    $sql = '
        CREATE TABLE `wa_agreement_log` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `create_datetime` DATETIME NOT NULL,
            `app_id` VARCHAR(64) NOT NULL,
            `context` VARCHAR(64) NOT NULL,
            `domain` VARCHAR(64) NOT NULL,
            `form_url` VARCHAR(255) DEFAULT NULL,
            `document_name` VARCHAR(64) NOT NULL,
            `document_id` INT DEFAULT NULL,
            `contact_id` INT DEFAULT NULL,
            `ip` VARCHAR(39) DEFAULT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `accept_method` VARCHAR(64) DEFAULT NULL,
            INDEX `app_id_context` (`app_id`, `context`),
            INDEX `contact_id` (`contact_id`)
        )
    ';
    $m->exec($sql);
}

try {
    $m->query("SELECT 1 FROM wa_agreement_document LIMIT 0");
} catch (waDbException $e) {
    $sql = '
        CREATE TABLE `wa_agreement_document` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `app_id` VARCHAR(64) NOT NULL,
            `context` VARCHAR(64) NOT NULL,
            `domain` VARCHAR(64) NOT NULL,
            `locale` VARCHAR(8) NOT NULL,
            `document_name` VARCHAR(64) NOT NULL,
            `document_text` TEXT NOT NULL,
            INDEX `app_id_context_domain_document_locale` (`app_id`, `context`, `domain`, `document_name`, `locale`)
        )
    ';
    $m->exec($sql);
}
