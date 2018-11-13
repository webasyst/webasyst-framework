<?php

$files = array(
    'templates/actions/system',
    'lib/actions/systemSettings',
);

foreach ($files as $f) {
    try {
        waFiles::delete(wa()->getAppPath($f, 'site'));
    } catch (waException $e) {

    }
}