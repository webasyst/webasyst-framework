<?php

//
// Update mailer_message_log.name, mailer_message_log.group
//

$replace_values = array();
$replace_sql = "INSERT INTO `mailer_message_recipients` (`id`, `group`, `name`) VALUES %s ON DUPLICATE KEY UPDATE `group`=VALUES(`group`), `name`=VALUES(`name`)";

$mlm = new mailerMessageRecipientsModel();

$categories = null;

foreach($mlm->where('name IS NULL')->limit(0)->query() as $row) {
    if (count($replace_values) > 50) {
        $mlm->exec(sprintf($replace_sql, implode(',', $replace_values)));
        $replace_values = array();
    }

    $value = $row['value'];
    if ($row['name'] || !strlen($value) || $value{0} == '@') {
        continue;
    }

    // Is it subscribers list id?
    if (wa_is_int($value)) {
        $replace_values[] = sprintf("(%d,'%s','%s')", $row['id'], $mlm->escape(_w('Subscribers')), $mlm->escape(_w('All subscribers')));
        continue;
    }

    // Is it a ContactsCollection hash?
    if ($value{0} == '/') {
        if (FALSE !== strpos($value, '/category/')) {
            $category_id = explode('/', $value);
            $category_id = end($category_id);
            if ($category_id && wa_is_int($category_id)) {
                if ($categories === null) {
                    $ccm = new waContactCategoryModel();
                    $categories = $ccm->getNames();
                }
                $replace_values[] = sprintf("(%d,'%s','%s')", $row['id'], $mlm->escape(_w('Categories')), $mlm->escape(ifset($categories[$category_id], $category_id)));
            }
        } else if (FALSE !== strpos($value, '/locale=')) {
            $locale = explode('=', $value);
            $locale = end($locale);
            $name = null;
            if ($locale) {
                $l = waLocale::getInfo($locale);
                if ($l) {
                    $name = $l['name'];
                }
            }
            $replace_values[] = sprintf("(%d,'%s','%s')", $row['id'], $mlm->escape(_w('Languages')), $mlm->escape($name));
        } else if ($value == '/') {
            $replace_values[] = sprintf("(%d,NULL,'%s')", $row['id'], $mlm->escape(_w('All contacts')));
        }
        continue;
    }

    // Otherwise, ot is a list of emails
    $replace_values[] = sprintf("(%d,NULL,'%s')", $row['id'], $mlm->escape(_w('Additional emails')));
}

if ($replace_values) {
    $mlm->exec(sprintf($replace_sql, implode(',', $replace_values)));
}
unset($replace_values);

$mlm->exec('UPDATE mailer_message_recipients SET name=value WHERE name IS NULL');
