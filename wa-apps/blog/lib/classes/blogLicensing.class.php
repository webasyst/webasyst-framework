<?php

/**
 * Class blogLicensing
 */
class blogLicensing
{
    /**
     * @return bool
     * @throws waDbException
     * @throws waException
     */
    public static function isPremium()
    {
        $is_premium = wa()->getSetting('license_premium', '', 'blog');
        if ($is_premium) {
            return true;
        }

        if (waLicensing::check('blog')->hasPremiumLicense()) {
            $app_settings = new waAppSettingsModel();
            $app_settings->set('blog', 'license_premium', date('Y-m-d H:i:s'));
            return true;
        }

        return false;
    }
}
