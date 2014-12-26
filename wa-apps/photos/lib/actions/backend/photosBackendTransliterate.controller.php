<?php

class photosBackendTransliterateController extends waJsonController
{
    public function execute()
    {
        $this->getResponse()->addHeader('Content-type', 'application/json');
        $t = waRequest::post('t', '', waRequest::TYPE_STRING_TRIM);
        $t = preg_replace('/\s+/', '-', $t);

        if ($t) {
            foreach (waLocale::getAll() as $lang) {
                $t = waLocale::transliterate($t, $lang);
            }
            $t = preg_replace('/[^a-zA-Z0-9_-]+/', '', $t);
        }

        $this->response = array(
            't' => strtolower($t),
            'placeholder' => date('Ymd'),
        );
    }
}
