<?php

interface waiLocaleAdapter
{
    public function load($locale, $locale_path, $domain, $textdomain = true);
    public function isLoaded($locale, $locale_path, $domain);
    public function gettext($msgid);
    public function ngettext($msgid1, $msgid2, $n);
    public function dgettext($domain, $msgid);
    public function dngettext($domain, $msgid1, $msgid2, $n);
}