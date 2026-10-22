<?php

class siteMenuLinksConfig
{
    public const SITEMAP_LINK_TYPE = 'sitemap';
    public const ANCHOR_LINK_TYPE = 'anchor';
    public const CUSTOM_LINK_TYPE = 'custom';
    public const PHONE_LINK_TYPE = 'phone';
    public const EMAIL_LINK_TYPE = 'email';
    public const FILE_LINK_TYPE = 'file';

    public const NOFOLLOW_REL = 'nofollow';
    public const SPONSORED_REL = 'sponsored';
    public const NOREFERRER_REL = 'noreferrer';

    public static function getLinkTypes()
    {
        return [
            self::SITEMAP_LINK_TYPE => [
                'name' => _w('Website page or section'),
                'icon_class' => 'sitemap',
            ],
            self::ANCHOR_LINK_TYPE => [
                'name' => _w('Website page block'),
                'icon_class' => 'anchor',
                'description' => sprintf_wp(
                    'To point the link to a certain block on a page, add an identifier to that block which is saved in the link settings. <a %s>Read more about anchor links</a> %s',
                    'href="#" class="nowrap"',
                    '<i class="fas fa-external-link-alt"></i>'
                ),
            ],
           self::CUSTOM_LINK_TYPE => [
                'name' => _w('Custom URL'),
                'icon_class' => 'globe',
                'description' => _w('It can be a link pointing either to an internal website page or to other sites. You can use it to lead visitors to certain catalog filters or to addresses containing UTM parameters.'),
            ],
            self::PHONE_LINK_TYPE => [
                'name' => _w('Phone'),
                'icon_class' => 'phone-alt',
                'description' => _w('Clicking the link will open the device’s phone app.'),
            ],
            self::EMAIL_LINK_TYPE => [
                'name' => _w('Email'),
                'icon_class' => 'envelope',
                'description' => _w('The link will open an email client with a new message to the specified address.'),
            ],
            self::FILE_LINK_TYPE => [
                'name' => _w('File'),
                'icon_class' => 'file-alt',
            ],
        ];
    }

    public static function getLinkRel()
    {
        return [
            self::NOFOLLOW_REL => [
                'name' => _w('Hide from search engines (nofollow)'),
                'tooltip' => _w('Search engines will be advised not to follow this link to index the page it is pointing to.'),
            ],
            self::SPONSORED_REL => [
                'name' => _w('Ad or partner link (sponsored)'),
                'tooltip' => _w('The link is used for a paid publication or a partner program.'),
            ],
            self::NOREFERRER_REL => [
                'name' => _w('Hide referrer information (noreferrer)'),
                'tooltip' => _w('The target page’s site will not determine the traffic source: “No referrer”.'),
            ],
        ];
    }

    public static function getDomainConfig(): array
    {
        $domain = siteHelper::getDomain();
        $domain_config_path = wa('site')->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        return [$domain_config, $domain_config_path];
    }

    public static function getDomainLinks(): array
    {
        list($domain_config) = self::getDomainConfig();
        return ifset($domain_config, 'apps', []);
    }
}
