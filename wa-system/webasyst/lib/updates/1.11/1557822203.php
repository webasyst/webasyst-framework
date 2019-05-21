<?php

$_im_field = waContactFields::get('im');
if ($_im_field instanceof waContactStringField) {
    $_ext_variants = $_im_field->getParameter('ext');
    if (is_array($_ext_variants) && !isset($_ext_variants['telegram'])) {
        $_ext_variants['telegram'] = 'Telegram';
        $_im_field->setParameter('ext', $_ext_variants);
        try {
            waContactFields::updateField($_im_field);
        } catch (waException $e) {

        }
    }
}
