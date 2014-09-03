<?php

// add social netform field
$field = waContactFields::get('socialnetwork', 'all');
if ($field->getType() === 'SocialNetwork') {
    $sort = 0;
    foreach (waContactFields::getAll('person') as $field_id => $fld) {
        if ($field_id == 'im') {
            break;
        }
        $sort += 1;
    }
    waContactFields::updateField($field);
    waContactFields::enableField($field, 'person', $sort);
}

// socail network field add domain parameter
$f = waContactFields::get('socialnetwork');
$f->setParameter('domain', array(
            'facebook' => 'facebook.com',
            'vkontakte' => 'vk.com',
            'twitter' => 'twitter.com',
            'linkedin' => null
));
waContactFields::updateField($f);

// Update birthday field
$field = waContactFields::get('birthday');
if ($field && $field instanceof waContactDateField) {
    $params = $field->getParameters();
    $new_field = new waContactBirthdayField('birthday', 'Birthday', array('storage' => 'info', 'prefix' => 'birth'));
    $params = array_merge($params, $new_field->getParameters());
    $new_field->setParameters($params);
    waContactFields::updateField($new_field);
}

$field = waContactFields::get('birthday');
if ($field) {
    $params = $field->getParameters();
    if (isset($params['formats'])) {
        $params['formats'] = array();
    }
    if (isset($params['validators'])) {
        $params['validators'] = array();
    }
    $params['storage'] = 'info';
    $field->setParameters($params);
    waContactFields::updateField($field);
}

// Updaten address field: enable lan and lng subfields of address field
$field = waContactFields::get('address', 'all');
if ($field) {
    $found_lng = false;
    $found_lat = false;
    $fields = (array) $field->getParameter('fields');
    foreach ($fields as $fld) {
        if ($fld->getId() == 'lng') {
            $found_lng = true;
            continue;
        }
        if ($fld->getId() == 'lat') {
            $found_lat = true;
            continue;
        }
        if ($found_lng && $found_lat) {
            break;
        }
    }
    if (!$found_lat) {
        $fields[] = new waContactHiddenField('lat', 'Latitude');
    }
    if (!$found_lng) {
        $fields[] = new waContactHiddenField('lng', 'Longitude');
    }
    if (!$found_lat || !$found_lng) {
        $field->setParameter('fields', $fields);
        waContactFields::updateField($field);
    }
}

// strtolower ext
$fields = array('email', 'phone', 'address', 'url');
foreach ($fields as $f_id) {
    $f = waContactFields::get($f_id);
    if ($f) {
        $ext = $f->getParameter('ext');
        foreach ($ext as $k => $v) {
            $ext[$k] = strtolower($v);
        }
        $f->setParameter('ext', $ext);
        waContactFields ::updateField($f);
    }
}

// enable company contact id
$f = waContactFields::get('company_contact_id', 'all');
if ($f) {
    waContactFields::updateField($f);
    waContactFields::enableField($f, 'person');
    waContactFields::enableField($f, 'company');
}

// rename en_US name 'Sex' to 'Gender'

$field = waContactFields::get('sex', 'all');
if ($field && $field->getParameter('storage') == 'info') {
    $p = $field->getParameter('localized_names');
    if (isset($p['en_US']) && $p['en_US'] == 'Sex') {
        $p['en_US'] = 'Gender';
        $field->setParameter('localized_names', $p);
        waContactFields::updateField($field);
    }
}

// make enable main fields for person
$main_fields = array(
    'name', 'title', 'firstname', 'middlename', 'lastname', 'jobtitle', 'company'
);
$sort = 0;
foreach ($main_fields as $f_id) {
    $field = waContactFields::get($f_id, 'all');
    if ($field) {
        waContactFields::updateField($field);
        waContactFields::enableField($field, 'person', $sort);
        $sort += 1;
    }
}

// make enable main fields for company
$main_fields = array('name', 'company');
$sort = 0;
foreach ($main_fields as $f_id) {
    $field = waContactFields::get($f_id, 'all');
    if ($field) {
        waContactFields::updateField($field);
        waContactFields::enableField($field, 'company', $sort);
        $sort += 1;
    }
}


