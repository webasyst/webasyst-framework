<?php
//
// When this installation has a custom person_fields_order config,
// make sure default set of fields is allowed in personal profile to edit.
//

if (!file_exists($this->getConfigPath('person_fields_order.php', true, 'contacts'))) {
    return;
}

$person_fields_default_file = $this->getRootPath().'/wa-system/contact/data/person_fields_default.php';
if (!is_readable($person_fields_default_file)) {
    return;
}

$person_fields_default = include($person_fields_default_file);
if (!$person_fields_default || !is_array($person_fields_default)) {
    return;
}

foreach($person_fields_default as $f_id => $opts) {
    if (!empty($opts['allow_self_edit'])) {
        $f = waContactFields::get($f_id, 'person');
        if ($f) {
            $f->setParameter('allow_self_edit', true);
            waContactFields::enableField($f, 'person');
        }
    }
}

