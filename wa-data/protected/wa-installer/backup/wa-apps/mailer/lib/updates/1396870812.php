<?php
// add new column and migrate data

$mod = new waModel();

try {
    // if table already has this column - all done (data transfered, old columns and PK deleted, new PK added), we are good
    $mod->exec('SELECT `contact_email_id` FROM `mailer_subscriber` WHERE 0');
} catch (waDbException $e) {
    // else add column
    $mod->exec('ALTER TABLE `mailer_subscriber` ADD `contact_email_id` INT(11) UNSIGNED NOT NULL DEFAULT 0');

    // try add data to new column
    // 1.
    $mod->exec("UPDATE mailer_subscriber s JOIN wa_contact_emails e
                ON s.contact_id = e.contact_id AND s.email = e.email
                SET s.contact_email_id = e.id");
    // 2.
    $mod->exec("UPDATE mailer_subscriber s JOIN wa_contact_emails e
                ON s.email = e.email
                SET s.contact_id = e.contact_id, s.contact_email_id = e.id
                WHERE s.contact_email_id = 0");

    // 3
    $rows = $mod->query("SELECT s.*, MAX(e.sort) as sort FROM mailer_subscriber s JOIN wa_contact_emails e
                         ON s.contact_id = e.contact_id WHERE s.contact_email_id = 0 GROUP BY s.contact_id")->fetchAll();
    if ($rows) {
        $email_model = new waContactEmailsModel();
        foreach ($rows as $row) {
            $email_id = $email_model->insert(array(
                'contact_id' => $row['contact_id'],
                'email' => $row['email'],
                'status' => 'unknown',
                'ext' => '',
                'sort' => $row['sort'] + 1
            ));
            $mod->exec("UPDATE mailer_subscriber SET contact_email_id = ".(int)$email_id."
                        WHERE list_id = ".(int)$row['list_id'].' AND contact_id = '.(int)$row['contact_id']." AND email = '".$mod->escape($row['email'])."'");
        }
    }

    // 4
    $rows = $mod->query("SELECT * FROM mailer_subscriber WHERE contact_email_id = 0")->fetchAll();
    if ($rows) {
        $email_model = new waContactEmailsModel();
        foreach ($rows as $row) {
            // contact exists
            if ($row['contact_id']) {
                if ($mod->query("SELECT 1 FROM wa_contact WHERE id = ".(int)$row['contact_id'])->fetch()) {
                    $email_id = $email_model->insert(array(
                        'contact_id' => $row['contact_id'],
                        'email' => $row['email'],
                        'status' => 'unknown',
                        'ext' => '',
                        'sort' => 0
                    ));
                    $mod->exec("UPDATE mailer_subscriber SET contact_email_id = ".(int)$email_id."
                        WHERE list_id = ".(int)$row['list_id'].' AND contact_id = '.(int)$row['contact_id']." AND email = '".$mod->escape($row['email'])."'");
                    return;
                }
            }

            $c = new waContact();
            if (!$c->save(array('email' => $row['email'], 'create_contact_id' => 0))) {
                $contact_id = $c->getId();
                // get email id
                $email = $email_model->getByField('contact_id', $contact_id);
                $mod->exec("UPDATE mailer_subscriber SET contact_id = ".(int)$contact_id.", contact_email_id = ".(int)$email['id']."
                        WHERE list_id = ".(int)$row['list_id'].' AND contact_id = '.(int)$row['contact_id']." AND email = '".$mod->escape($row['email'])."'");
            }
        }
    }

    // drop PK and columns
    $mod->exec("ALTER TABLE `mailer_subscriber` DROP PRIMARY KEY");
    try {
        $mod->exec("ALTER TABLE `mailer_subscriber` DROP `email`");
    } catch (waDbException $e) {
    }
    // add new PK
    $mod->exec("ALTER TABLE `mailer_subscriber` ADD PRIMARY KEY (`list_id`, `contact_id`, `contact_email_id`)");

    try {
        $mod->exec("ALTER TABLE `mailer_subscriber` ADD INDEX `contact_email_id` (`contact_email_id`)");
    } catch (waDbException $e) {
    }
}