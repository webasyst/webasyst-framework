<?php

$mod = new waModel();

try {
    $mod->exec('ALTER TABLE `mailer_subscriber`
                    DROP PRIMARY KEY,
                    ADD PRIMARY KEY (`list_id`, `contact_id`, `email`),
                    ADD INDEX `contact_id` (`contact_id`),
                    ADD INDEX `email` (`email`)');
} catch (waDbException $e) {
}

