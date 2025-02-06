<?php
$m = new waModel();

try {
    $m->query("SELECT 1 FROM wa_announcement_reactions LIMIT 0");
} catch (waDbException $e) {
    $sql = '
        CREATE TABLE `wa_announcement_reactions` (
          `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `announcement_id` INT NOT NULL,
          `create_datetime` DATETIME NOT NULL,
          `contact_id` INT NOT NULL,
          `reaction` VARCHAR(1) CHARACTER SET %s COLLATE %1$s_bin NULL DEFAULT NULL,
          UNIQUE KEY `announcement_reaction_contact` (`announcement_id`, `reaction`, `contact_id`)
        )
    ';
    try {
        $m->query(sprintf($sql, 'utf8mb4'));
    } catch (waDbException $e) {
        if ($e->getCode() == 1115) {
            // DB does not support utf8mb4
            $m->query(sprintf($sql, 'utf8'));
        } else {
            throw $e;
        }
    }
}

try {
    $m->query("SELECT 1 FROM wa_announcement_comments LIMIT 0");
} catch (waDbException $e) {
    $sql = '
        CREATE TABLE `wa_announcement_comments` (
          `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `announcement_id` INT NOT NULL,
          `create_datetime` DATETIME NOT NULL,
          `update_datetime` DATETIME NULL DEFAULT NULL,
          `contact_id` INT NOT NULL,
          `text` TEXT NULL DEFAULT NULL,
          KEY `announcement_datetime` (`announcement_id`, `create_datetime`)
        )
    ';
    $m->query($sql);
}
