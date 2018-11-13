<?php

class webasystSettingsFieldTransliterateController extends webasystSettingsJsonController
{
    public function execute()
    {
        $names = $this->getRequest()->post('name', "");
        if (empty($names)) {
            $this->response = "";
        } else {
            $this->response = $this->getTransliteratedId($names);
        }
    }

    protected function getTransliteratedId($names)
    {
        $id = null;

        if (!empty($names['en_US'])) {
            $id = strtolower($this->transliterate($names['en_US']));
        } else {
            if (isset($names['en_US'])) {
                unset($names['en_US']);
            }
            $id = strtolower($this->transliterate(reset($names)));
        }

        return $id;
    }

    public static function transliterate($str, $strict = true)
    {
        if (empty($str)) {
            return "";
        }
        $str = preg_replace('/\s+/u', '_', $str);
        if ($str) {
            foreach (waLocale::getAll() as $lang) {
                $str = waLocale::transliterate($str, $lang);
            }
        }
        $str = preg_replace('/[^a-zA-Z0-9_-]+/', '', $str);
        if ($strict && !$str) {
            $str = date('Ymd');
        }
        return strtolower($str);
    }
}