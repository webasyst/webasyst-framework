<?php

$_im_field = waContactFields::get('socialnetwork');
if ($_im_field instanceof waContactStringField) {
    $_ext_variants = $_im_field->getParameter('ext');
    if (is_array($_ext_variants)) {
        if (!isset($_ext_variants['instagram'])) {
            $_ext_variants['instagram'] = 'Instagram';
            $_im_field->setParameter('ext', $_ext_variants);
            try {
                waContactFields::updateField($_im_field);
            } catch (waException $e) {
                waLog::log($e->getMessage());
            }
        }
    }
}
