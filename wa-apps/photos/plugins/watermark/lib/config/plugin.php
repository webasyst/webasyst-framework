<?php

return array(
    'name' => 'Watermark',//_wp('Watermark')
    'description' => 'Applies watermark text or image on uploaded photos',
    'img' => 'img/watermark.png',
    'vendor' => 'webasyst',
    'version' => '1.0.0',
    'rights' => false,
    'handlers' => array(
        'photo_upload' => 'photoUpload',
    )
);