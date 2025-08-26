<?php

class webasystApiLicenseCacheController extends waController
{
    public function execute()
    {
        $token = waRequest::get('token', null, waRequest::TYPE_STRING_TRIM);
        $store_token = null;
        if ($token_data = (new waAppSettingsModel)->get('installer', 'token_data', false)) {
            $token_data = waUtils::jsonDecode($token_data, true);
            if (!empty($token_data['token'])) {
                $store_token = $token_data['token'];
            }
        }
        if (empty($token) || $token != $store_token) {
            // do nothing
            return;
        }

        // Clear licenses cache
        $cache = new waVarExportCache('licenses', -1, 'installer');
        $cache->delete();

        // Clear announcements cache
        if (wa()->appExists('installer')) {
            wa('installer')->getConfig()->clearAnnouncementsCache();
        }
    }
}