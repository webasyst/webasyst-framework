<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-installer
 */

class waInstallerApps
{
    private $installed_apps = array();
    private $installed_extras = array();
    private $sources;
    private static $root_path;
    private static $locale;
    private static $cache_ttl;
    private $license;
    private $identity_hash;
    private $beta;
    private $promo_id;
    private static $force;

    private $extras_list = array();
    private static $sort = array();

    const CONFIG_GENERIC = 'wa-config/config.php';
    const CONFIG_DB = 'wa-config/db.php';
    const CONFIG_APPS = 'wa-config/apps.php';
    const CONFIG_APP_PLUGINS = 'wa-config/apps/%s/plugins.php';
    const CONFIG_ROUTING = 'wa-config/routing.php';
    const CONFIG_SOURCES = 'wa-installer/lib/config/sources.php';

    const ITEM_CONFIG = 'wa-apps/%s/lib/config/app.php';
    const ITEM_ROBOTS = 'wa-apps/%s/lib/config/robots.txt';
    const ITEM_REQUIREMENTS = 'wa-apps/%s/lib/config/requirements.php';
    const ITEM_BUILD = 'wa-apps/%s/lib/config/build.php';
    const ITEM_EXTRAS = 'wa-apps/%s/%s/%s/%s%s.php';
    const ITEM_EXTRAS_PATH = 'wa-apps/%s/%s/';
    const ITEM_ICON = 'img/%s.png';

    const PLUGIN_CONFIG = 'wa-plugins/%s/%s/lib/config/plugin.php';
    const PLUGIN_REQUIREMENTS = 'wa-plugins/%s/%s/lib/config/requirements.php';
    const PLUGIN_BUILD = 'wa-plugins/%s/%s/lib/config/build.php';

    const VENDOR_SELF = 'webasyst';
    const VENDOR_UNKNOWN = 'local';

    const LIST_APPS = 'apps';
    const LIST_SYSTEM = 'system';

    const ACTION_UPDATE = 'update';
    const ACTION_CRITICAL_UPDATE = 'critical';
    const ACTION_REPAIR = 'repair';
    const ACTION_INSTALL = 'install';
    const ACTION_NONE = 'none';

    /**
     * Item's statuses list at common config
     */

    /**
     *
     * Item enabled at config
     * @var string
     */
    const STATUS_ENABLED = 'enabled';
    /**
     *
     * Item disabled at config
     * @var string
     */
    const STATUS_DISABLED = 'disabled';
    /**
     *
     * Item not present at config
     * @var string
     */
    const STATUS_DELETED = 'deleted';

    /**
     * Get install hash
     * @return string
     */
    public function getHash()
    {
        return $this->identity_hash;
    }

    /**
     * Get install promo_id
     * @return string
     */
    public function getPromoId()
    {
        return $this->promo_id;
    }

    private static function getServerSignature($raw = false)
    {
        $signature = array(
            'php' => preg_replace('@([^0-9\\.].*)$@', '', phpversion()),
            'c'   => PHP_INT_SIZE,
            'api' => PHP_SAPI,
        );
        if (function_exists('php_uname')) {
            $signature['os'] = @php_uname('s');
            $signature['r'] = @php_uname('r');
        } elseif (defined('PHP_OS')) {
            $signature['os'] = constant('PHP_OS');
        }
        return $raw ? $signature : base64_encode(json_encode($signature));
    }

    private static $app_domains = array();

    private static function getDomains($apps, $raw = false)
    {
        $d = null;
        $a = array();
        if (function_exists('wa')) {
            $wa = wa();
            if ($wa && is_object($wa) && method_exists($wa, 'getRouting')) {
                if (($routing = $wa->getRouting()) && method_exists($routing, 'getByApp')) {
                    $d = array();
                    foreach ($apps as $app_id) {
                        if (!in_array($app_id, array('installer', 'webasyst', 'site',))) {
                            $app_domains = $routing->getByApp($app_id);
                            foreach ($app_domains as $domain => $route) {
                                if ($i = strpos($domain, '/')) {
                                    $domain = substr($domain, 0, $i);
                                }
                                if (strpos($domain, '.')) {
                                    $domain = preg_replace('@:\d+@', '', $domain);
                                    if (!in_array($domain, $d)) {
                                        $d[] = $domain;
                                    }
                                    if (!isset(self::$app_domains[$app_id])) {
                                        self::$app_domains[$app_id] = array();
                                    }
                                    if (!in_array($domain, self::$app_domains[$app_id])) {
                                        self::$app_domains[$app_id][] = $domain;
                                    }

                                    $id = array_search($domain, $d);
                                    if (!isset($s[$app_id])) {
                                        $s[$app_id] = array();
                                    }
                                    $s[$app_id][] = $id;
                                }
                            }
                            if (!empty($s[$app_id])) {
                                $a[$app_id] = 0;
                                foreach ($s[$app_id] as $id) {
                                    $a[$app_id] += 1 << $id;
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!$raw && $d) {
            $d = implode(':', $d);
        }

        $result = compact('a', 'd');
        return $raw ? $result : http_build_query($result);
    }

    /**
     * Get install domain
     * @return mixed
     */
    public function getDomain()
    {
        static $domain = null;
        if ($domain === null) {
            $domain = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
            $domain = preg_replace('@(^www\.|:\d+$)@', '', $domain);
        }
        return $domain;
    }

    private static function init()
    {
        if (!isset(self::$root_path)) {
            @ini_set("magic_quotes_runtime", 0);
            if (version_compare('5.4', PHP_VERSION, '>') && function_exists('set_magic_quotes_runtime') && get_magic_quotes_runtime()) {
                @set_magic_quotes_runtime(false);
            }
            @ini_set('register_globals', 'off');

            self::$root_path = preg_replace('@([/\\\\]+)@', '/', dirname(__FILE__).'/');
            //XXX fix root path definition
            self::$root_path = preg_replace('@(/)wa-installer/lib/classes/?$@', '$1', self::$root_path);
        }

    }

    public static function setLocale($locale = null)
    {
        if (!is_null($locale) && $locale) {
            self::$locale = $locale;
        }
        self::init();
    }

    public function __construct($license = null, $locale = null, $ttl = 600, $force = false)
    {
        self::init();
        $this->license = $license;
        /* identity hash */
        $this->identity_hash = self::getGenericConfig('identity_hash');
        $this->beta = self::getGenericConfig('beta');
        if (in_array($this->beta, array(true, '1', 1), true)) {
            $this->beta = 'beta';
        }
        $this->promo_id = self::getGenericConfig('promo_id');
        if (!$this->identity_hash) {
            $this->updateGenericConfig();
            $this->identity_hash = self::getGenericConfig('identity_hash');
        }
        self::setLocale($locale);
        self::$cache_ttl = max(0, $ttl);
        self::$force = $force;

        /* enabled items list */
        if (false && file_exists(self::$root_path.self::CONFIG_APPS)) {
            $this->installed_apps = include(self::$root_path.self::CONFIG_APPS);
            foreach ($this->installed_apps as $app_id => & $enabled) {
                if ($enabled) {
                    $this->installed_extras[$app_id] = array();
                    $this->installed_extras[$app_id]['plugins'] = self::getConfig(sprintf(self::CONFIG_APP_PLUGINS, $app_id));
                    $this->installed_extras[$app_id]['themes'] = self::getFolders(sprintf(self::ITEM_EXTRAS_PATH, $app_id, 'themes'));
                    $build_path = self::$root_path.sprintf(self::ITEM_BUILD, $app_id);
                    if (file_exists($build_path)) {
                        $enabled = max(1, include($build_path));
                    } else {
                        $enabled = 1;
                    }

                }
                unset($enabled);
            }
        }
        if (file_exists(self::$root_path.self::CONFIG_SOURCES)) {
            $this->sources = include(self::$root_path.self::CONFIG_SOURCES);
        }

        //TODO USE config or etc
        $this->extras_list['plugins'] = array(
            'info'    => 'plugin',
            'subpath' => 'lib/config/',
        );
        $this->extras_list['themes'] = array(
            'info'    => 'theme',
            'subpath' => '',
        );
    }

    private function getVendors()
    {
        return array(self::VENDOR_SELF);
    }

    private function getSources($key, $vendors = array())
    {
        if (!is_array($vendors) && $vendors) {
            $vendors = array($vendors);
        }

        if (!$this->sources) {
            throw new Exception('Empty sources list');
        }

        if (empty($this->sources[$key])) {
            throw new Exception(sprintf('Not found sources for %s', $key));
        }
        $sources = array();
        foreach ((array)$this->sources[$key] as $vendor => $source) {
            if (!$vendor) {
                $vendor = self::VENDOR_SELF;
            }
            if (!$vendors || in_array($vendor, $vendors)) {
                $sources[$vendor] = $source;
            }
        }
        return $sources;
    }

    /**
     *
     * @param $requirements
     * @param bool $update_config
     * @param bool $action
     * @return boolean
     */
    public static function checkRequirements(&$requirements, $update_config = false, $action = false)
    {
        self::init();
        if (is_null($requirements)) {
            $requirements = self::getRequirements('wa-installer/lib/config/requirements.php', 'installer');
        }
        $passed = true;
        $config = array();
        $actions = array(
            self::ACTION_CRITICAL_UPDATE,
            self::ACTION_UPDATE,
            self::ACTION_INSTALL,
        );
        $update = $action && in_array($action, $actions);
        foreach ($requirements as $subject => & $requirement) {
            $requirement['passed'] = false;
            $requirement['note'] = null;
            $requirement['warning'] = false;
            $requirement['update'] = $update;

            waInstallerRequirements::test($subject, $requirement);

            $passed = $requirement['passed'] && $passed;
            if ($update_config && isset($requirement['config'])) {
                $config[$requirement['config']] = $requirement['value'];
            }
            if ($requirement['note'] && isset($requirement['allow_skip']) && $requirement['allow_skip']) {
                unset($requirement);
                unset($requirements[$subject]);
            } else {
                unset($requirement);
            }
        }
        if ($update_config) {
            try {
                self::updateGenericConfig($config);
            } catch (Exception $e) {
                $requirements[] = array(
                    'name'        => '',
                    'passed'      => false,
                    'warning'     => $e->getMessage(),
                    'description' => '',
                    'note'        => '',
                );
                $passed = false;
            }
        }

        return $passed;
    }

    /**
     *
     * @param $item
     * @param $config
     * @return boolean
     */
    private static function checkVendor($item, $config = null)
    {
        $applicable = false;
        if (is_null($config) && isset($item['installed'])) {
            $config = $item['installed'];
        }
        if ($config) {
            if (isset($config['vendor'])) {
                if (isset($item['vendor'])) {
                    $applicable = (strcasecmp($config['vendor'], $item['vendor']) == 0) ? true : false;
                    if ($applicable) {
                        if (isset($item['edition'])) {
                            $applicable = (strcasecmp($config['edition'], $item['edition']) == 0) ? true : false;
                        } elseif (!empty($config['edition'])) {
                            $applicable = false;
                        }
                    }
                } else {
                    //TODO
                }

            } else {
                //XXX allow update, while vendor missing
                $applicable = false;
            }
        }
        return $applicable;
    }

    /**
     *
     * @param $path
     * @param $slug
     * @return array
     */
    private static function getRequirements($path, $slug)
    {
        $requirements = self::getConfig($path);
        $fields = array('name', 'description');
        foreach ($requirements as & $requirement) {
            foreach ($fields as $field) {
                if (isset($requirement[$field]) && is_array($requirement[$field])) {
                    if (self::$locale && isset($requirement[$field][self::$locale])) {
                        $value = $requirement[$field][self::$locale];
                    } elseif (isset($requirement[$field]['en_US'])) {
                        $value = $requirement[$field]['en_US'];
                    } else {
                        $value = array_shift($requirement[$field]);
                    }
                    $requirement[$field] = $value;
                } elseif (!isset($requirement[$field])) {
                    $requirement[$field] = '';
                } else {
                    $requirement[$field] = _wd($slug, $requirement[$field]);
                }
            }
            if (!isset($requirement['strict'])) {
                $requirement['strict'] = false;
            }
            unset($requirement);
        }
        return $requirements;
    }

    public static function getGenericConfig($property = null, $default = false)
    {
        self::init();
        $config = self::getConfig(self::CONFIG_GENERIC);
        if ($property) {
            return isset($config[$property]) ? $config[$property] : $default;
        } else {
            return $config;
        }
    }

    /**
     *
     * Enumerate local items
     * @since 2.0
     * @param string $path wa-apps/[%app_id%/(themes|plugins)/] or wa-plugins/(payment|shipping|sms)
     *
     * @param array $options
     *
     * @param array [string]array $options['items']
     * @param array [string]boolean $options['plugins']
     * @param array [string]boolean $options['themes']
     * @param array [string]boolean $options['list']
     *
     * @param array $filter
     * @param array [string]mixed $filter['locale']
     * @param array [string]mixed $filter['vendor']
     * @param array [string]boolean $options['plugins']
     * @param array [string]boolean $options['themes']
     * @return array
     */
    protected function enumerate($path, $options = array(), $filter = array())
    {
        $items = array();
        $path = self::formatPath($path);
        $folders = self::getFolders($path);
        $z = array();
        foreach ($folders as $slug => $exist) {
            if ($slug) {
                if (isset($options['items']) && !isset($options['items'][$slug])) {
                    #skip not applicable items
                    continue;
                }
                $z[] = $path;
                if ($item = $this->info($path, $slug, $filter)) {
                    if (isset($options['items'])) {
                        $item['enabled'] = !empty($options['items'][$slug]);
                    }
                    $items[$slug] = $item;
                }
            }
        }
        return $items;
    }

    private function info($path, $slug, $filter = array())
    {
        $item = null;
        $config_path = self::getConfigPath(trim($path.'/'.$slug, '/'));
        $config = $this->getConfig($config_path);
        if ($config) {
            $config += array(
                'vendor' => self::VENDOR_UNKNOWN,
            );
        }
        if (empty($filter) || self::filter($config, $filter)) {
            $item = array(
                'id'          => preg_replace('@^.+/([^/]+)$@', '$1', $slug),
                'slug'        => trim(preg_replace('@^/?wa-apps/@', '', $path.'/'.$slug), '/'),
                'path'        => $path.'/'.$slug,
                'config_path' => $config_path,
                'installed'   => $config ? $config : false,
            );
            if ($item['installed']) {
                self::fixItemVersion($item);
            }
        }
        return $item;
    }

    private static function getConfigPath($path)
    {
        $path = self::formatPath($path);
        if (preg_match('@^wa-apps/[^/]+(/.+|$)@', $path)) {
            #it apps or apps extras
            if (preg_match('@^wa-apps/([^/]+)/(themes|plugins)/([^/]+)$@', $path, $matches)) {
                switch ($matches[2]) {
                    case 'themes':
                        $path .= '/theme.xml';
                        break;
                    case 'plugins':
                        $path .= '/lib/config/plugin.php';
                        break;
                }
            } elseif (preg_match('@^wa-apps/([^/]+)/?$@', $path, $matches)) {
                $path .= '/lib/config/app.php';
            } else {
                $path = false;
            }
        } elseif (preg_match('@^wa-plugins/[^/]+/[^/]+@', $path)) {
            #system plugins

            $path = preg_replace('@^wa-plugins/([^/]+)/plugins/(.+)$@', 'wa-plugins/$1/$2', $path);
            $path .= '/lib/config/plugin.php';
        } else {
            $path = false;
        }
        return $path;
    }

    private static function formatPath($path)
    {
        $path = preg_replace('@([/\\\\]+)@', '/', $path);
        return preg_replace('@([/\\\\]+)$@', '', $path);
    }

    /**
     *
     * Filter item by params at filter
     * @param array $item
     * @param array $filter
     * @return bool
     */
    private static function filter($item, $filter = array())
    {
        $applicable = true;
        foreach ($filter as $field => $value) {
            if (isset($item[$field])) {
                $v = $item[$field];
                if (is_array($value)) {
                    if (is_array($item[$field])) {
                        if (!array_intersect($v, $value)) {
                            $applicable = false;
                            break;
                        }
                    } elseif (!in_array($v, $value)) {
                        $applicable = false;
                        break;
                    }
                } elseif ($value === true) { /* value required */
                    if (empty($v)) {
                        $applicable = false;
                        break;
                    }

                } elseif ($value === false) { /* value required */
                    $applicable = false;
                    break;
                } else {
                    if (is_array($value)) {
                        if (!in_array($value, $v)) {
                            $applicable = false;
                            break;
                        }
                    } elseif ($v != $value) {
                        $applicable = false;
                        break;
                    }
                }
            } elseif ($value === true) { /* value required */
                $applicable = false;
                break;
            }
        }
        return $applicable;
    }

    private function extend(&$item, $options = array())
    {
        if ($item) {
            self::fixItemCurrent($item);
            $item['applicable'] = true;
            if (!empty($item['requirements']) && !empty($options['requirements'])) {
                $item['applicable'] = $this->checkRequirements($item['requirements']);
            }
            if (!empty($options['action'])) {
                $item['action'] = self::applicableAction($item);
            }
        }
    }

    public function getItems($options = array())
    {
        static $items = null;
        if ($items === null) {
            $options += array(
                'installed' => true,
            );
            $extra_types = array(
                'plugins' => array(),
                'themes'  => array(),
            );
            $items = $this->getApps($options);
            foreach ($items as $app) {
                if (!empty($app['installed']['plugins'])) {
                    $extra_types['plugins'][] = $app['id'];
                }

                if (!empty($app['installed']['themes'])) {
                    $extra_types['themes'][] = $app['id'];
                }
            }
            $options['status'] = true;
            foreach ($extra_types as $type => $extras_apps) {
                $extras = $this->getExtras($extras_apps, $type, $options);
                foreach ($extras as $app_id => $app) {
                    foreach ($app[$type] as $extra) {
                        if (($type != 'themes') || ($extra['id'] != 'default')) {
                            $items[$app_id][$type][$extra['id']] = $extra;
                        }
                    }
                }
            }
        }
        return $items;
    }

    /**
     * @since 2.0
     * @param string $vendor
     * @param boolean $check_updates
     * @return array[string]
     * @todo filter vendor
     */
    public function getVersions($vendor = null, $check_updates = false)
    {
        if (empty($vendor)) {
            $vendor = self::VENDOR_SELF;

        }
        $versions = array();
        $apps = $this->getItems();
        $apps += $this->getSystemItems(true);
        foreach ($apps as $slug => $app) {
            if (!empty($app['installed']) && (strpos($slug, 'wa-plugins/') !== 0)) {
                $versions[$slug] = $app['installed']['version'];
            }

            foreach (array('plugins', 'themes') as $type) {
                if (!empty($app[$type])) {
                    foreach ($app[$type] as $item) {
                        $versions[$item['slug']] = $item['installed']['version'];
                    }
                }

            }
        }

        if ($check_updates) {
            $available = $this->query('versions/?slug='.implode(',', array_keys($versions)), $vendor);
            foreach ($versions as $slug => $version) {
                if (empty($available[$slug]) || version_compare($version, $available[$slug], '>=')) {
                    unset($versions[$slug]);
                }
            }
        } else {

        }
        return $versions;
    }

    /**
     * @param string $vendor
     * @param array $new_items list of new items
     * @return array
     */
    public function getUpdates($vendor = null, $new_items = array())
    {
        if (empty($vendor)) {
            $vendor = self::VENDOR_SELF;
        }
        $query = array();
        $versions = $this->getVersions($vendor, false);
        if ($new_items) {
            foreach ($new_items as $slug => $item) {
                $slug = preg_replace('@^wa-plugins/([^/]+)/plugins/(.+)$@', 'wa-plugins/$1/$2', $slug);
                $version = isset($versions[$slug]) ? $versions[$slug] : '0';
                $query[$slug] = sprintf('%s=%s', (sprintf('v[%s]', $slug)), rawurlencode($version));
            }
            //XXX TODO compare local & new edition & version
        } else {
            foreach ($versions as $slug => $version) {
                $slug = preg_replace('@^wa-plugins/([^/]+)/plugins/(.+)$@', 'wa-plugins/$1/$2', $slug);
                $query[$slug] = sprintf('%s=%s', (sprintf('v[%s]', $slug)), rawurlencode($version));
            }
        }
        if ($query) {
            $url = 'updates/?'.implode('&', $query);
            $updates = $this->query($url, $vendor, false);
            $items = $this->getItems();
            $items += $this->getSystemItems(true);
            //XXX check current edition:versions
            foreach ($updates as $app_id => &$item) {
                foreach (array('plugins', 'themes') as $type) {
                    if (!empty($item[$type])) {
                        foreach ($item[$type] as $extras_id => &$extras_item) {
                            if (!empty($items[$app_id][$type][$extras_id])) {
                                $extras_item = array_merge($items[$app_id][$type][$extras_id], $extras_item);
                            }
                            $extras_item['app'] = $app_id;
                            $extras_item['vendor'] = $vendor;
                            $extras_item['slug'] = $slug = $app_id.'/'.$type.'/'.$extras_id;
                            if (empty($extras_item['name'])) {
                                $extras_item['name'] = empty($extras_item['installed']['name']) ? $extras_item['slug'] : $extras_item['installed']['name'];
                            }
                            $extras_item['action'] = self::applicableAction($extras_item);
                            $extras_item['applicable'] = self::checkRequirements($extras_item['requirements'], false, $extras_item['action']);
                            $this->buildUrl($extras_item['download_url']);
                            unset($extras_item);
                        }
                    }
                }

                if (isset($item['version'])) {

                    if (!empty($items[$app_id])) {
                        if (isset($items[$app_id]['plugins'])) {
                            unset($items[$app_id]['plugins']);
                        }
                        if (isset($items[$app_id]['themes'])) {
                            unset($items[$app_id]['themes']);
                        }
                        $item = array_merge($items[$app_id], $item);
                    }
                    $item['vendor'] = $vendor;
                    $item['slug'] = $slug = $app_id;
                    $item['action'] = self::applicableAction($item);
                    $item['applicable'] = self::checkRequirements($item['requirements'], false, $item['action']);
                    $item['domains'] = isset(self::$app_domains[$app_id]) ? self::$app_domains[$app_id] : array();
                    $this->buildUrl($item['download_url']);
                } elseif (!empty($items[$app_id])) {
                    if (empty($item['name'])) {
                        $item['name'] = $items[$app_id]['installed']['name'];
                    }
                    $item['action'] = self::ACTION_NONE;
                }
                unset($item);
            }
        } else {
            $updates = array();
        }
        return $updates;
    }

    private function getSystemItems($extras = false)
    {
        $items = array(
            'wa-plugins/sms'      => array(
                'slug'      => 'wa-plugins/sms',
                'id'        => 'sms',
                'icon'      => array(
                    16 => 'icon16 mobile',
                ),
                'name'      => _w('SMS'),
                'installed' => true,
                'vendor'    => 'webasyst',
            ),
            'wa-plugins/payment'  => array(
                'slug'      => 'wa-plugins/payment',
                'id'        => 'payment',
                'icon'      => array(
                    16 => 'icon16 dollar',
                ),
                'name'      => _w('Payment'),
                'installed' => true,
                'vendor'    => 'webasyst',
            ),
            'wa-plugins/shipping' => array(
                'slug'      => 'wa-plugins/shipping',
                'id'        => 'shipping',
                'icon'      => array(
                    16 => 'icon16 box',
                ),
                'name'      => _w('Shipping'),
                'installed' => true,
                'vendor'    => 'webasyst',
            ),
        );
        foreach ($items as $id => &$item) {

            $item['plugins'] = array();
            $item['icons'] = $item['icon'];
            if (empty($item['plugins']) && false) {
                unset($items[$id]);
            }
            unset($item);
        }
        if ($extras) {
            $enum_options = array(
                'installed' => true,
            );
            $plugins = $this->getExtras(array_keys($items), 'plugins', $enum_options);

            foreach ($plugins as $id => $item) {
                if (isset($items[$id]) && !empty($item['plugins'])) {
                    $items[$id]['plugins'] = $item['plugins'];
                }

            }
        }
        return $items;
    }

    /**
     *
     * @param array $options
     *
     * @param array [string]string $options['status'] item status at config (enabled|disabled or deleted); default are enabled
     * @param array [string]boolean $options['requirements'] check apps requirements; default are false
     * @param array [string]boolean $options['installed'] get local apps only if true; get remote apps if false; merge if not set
     *
     * @param array $filter
     * @param array [string]string $filter['extras']
     * @return array
     * @since 2.0
     */
    public function getApps($options = array(), $filter = array())
    {
        /*get local apps*/
        $installed = array();
        $options += array(
            'status' => self::STATUS_ENABLED,
        );


        $enum_options = array();
        if (!empty($options['status'])) {
            $installed_apps = self::getConfig(self::CONFIG_APPS);
            switch ($options['status']) {
                case self::STATUS_ENABLED:
                    $installed_apps = array_filter($installed_apps, 'intval');
                    $enum_options['items'] = $installed_apps;
                    break;
                case self::STATUS_DISABLED:
                    $installed_apps = array_diff_assoc($installed_apps, array_filter($installed_apps, 'intval'));
                    $enum_options['items'] = $installed_apps;
                    break;
                case true:
                    $enum_options['items'] = $installed_apps;
                    break;
            }
        }

        $enum_filter = array();
        if (!empty($filter['extras'])) {
            $enum_filter[$filter['extras']] = true;
        }
        if (!isset($options['installed']) || !empty($options['installed'])) {
            $apps = $this->enumerate('wa-apps', $enum_options, $enum_filter);
            $installed = $apps;
        } else {
            $apps = array();
        }

        if (!empty($options['system'])) {
            $apps += $this->getSystemItems();
        }

        /* get available apps */
        if (empty($options['installed'])) {
            $list_filter = array();

            if (!empty($filter['extras'])) {
                $list_filter[$filter['extras']] = true;
            }

            if (isset($options['local']) && empty($options['local'])) {
                $apps = array();
            }

            foreach ($this->getVendors() as $vendor) {
                $url = 'apps/';
                if (!empty($options['filter'])) {
                    $filter = array();
                    foreach ($options['filter'] as $param => $value) {
                        $filter[] = "filter[{$param}]={$value}";
                    }
                    $url .= '?'.implode('&', $filter);
                }
                $list = $this->query($url, $vendor);
                foreach ($list as $app_id => $app) {
                    if (empty($list_filter) || self::filter($app, $list_filter)) {

                        if (!empty($installed[$app_id])) {
                            $apps[$app_id] = array_merge($installed[$app_id], $app);
                        } else {
                            $app += array(
                                'id'        => $app_id,
                                'slug'      => $app_id,
                                'vendor'    => $vendor,
                                'installed' => null,
                            );
                            $apps[$app_id] = $app;
                        }
                    } elseif (!empty($installed[$app_id])) {
                        unset($apps[$app_id]);
                    }
                }
            }
        }

        foreach ($apps as & $app) {
            $this->extend($app, $options);
        }
        unset($app);

        /* optional check applicable updates */

        return $apps;

    }

    /**
     * @since 2.0
     * @param $app
     * @param $type
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function getExtras($app, $type, $options = array())
    {
        if (!in_array($type, array('plugins', 'themes'))) {
            throw new Exception('Invalid extras type');
        }
        /*get local extras*/
        $options += array(
            'status' => self::STATUS_ENABLED,
        );

        $enum_options = array();
        if (!empty($options['status'])) {
            if ($type == 'plugins') {
                foreach ((array)$app as $app_id) {
                    if (strpos($app_id, 'wa-plugins/') === false) {
                        $installed = self::getConfig(sprintf(self::CONFIG_APP_PLUGINS, $app_id));
                        switch ($options['status']) {
                            case self::STATUS_ENABLED:
                                $installed = array_filter($installed, 'intval');
                                $enum_options[$app_id] = array('items' => $installed);
                                break;
                            case self::STATUS_DISABLED:
                                $installed = array_diff_assoc($installed, array_filter($installed, 'intval'));
                                $enum_options[$app_id] = array('items' => $installed);
                                break;
                            case true:
                                $enum_options[$app_id] = array('items' => $installed);
                                break;
                        }
                    }
                }
            }
        }

        if (!isset($options['installed']) || !empty($options['installed'])) {
            $extras = array();
            foreach ((array)$app as $app_id) {
                if (preg_match('@^wa-plugins/@', $app_id)) {
                    $path = $app_id;
                } else {
                    $path = 'wa-apps/'.$app_id.'/'.$type;
                }
                $enum_filter = array();
                if (!empty($options['filter'])) {
                    if (!empty($options['filter']['vendor'])) {
                        $enum_filter['vendor'] = $options['filter']['vendor'];
                    }
                }
                $extras[$app_id] = array(
                    $type => $this->enumerate($path, isset($enum_options[$app_id]) ? $enum_options[$app_id] : array(), $enum_filter),
                );
            }
            $installed = $extras;
        } else {
            $installed = array();
            $extras = array();
        }

        /* get available apps */
        if (empty($options['installed'])) {
            if (isset($options['local']) && empty($options['local'])) {
                $installed = $extras;
                $extras = array();
            }

            foreach ($this->getVendors() as $vendor) {

                $url = $type.'/?app_id='.implode(',', (array)$app);
                if (!empty($options['filter'])) {
                    foreach ($options['filter'] as $param => $value) {
                        $url .= "&filter[{$param}]={$value}";
                    }
                }
                if (!empty($options['inherited'])) {
                    $url .= '&inherited='.implode(',', (array)$options['inherited']);
                }

                $list = $this->query($url, $vendor);

                $count = count(self::$sort);
                foreach ($list as $app_id => $available_extras) {
                    if (!isset($extras[$app_id])) {
                        if (!isset($options['apps']) || !empty($options['apps'])) {
                            $enum_filter = array($type => true);
                            $extras[$app_id] = $this->info('wa-apps', $app_id, $enum_filter);
                        } else {
                            $extras[$app_id] = array($type => array());
                        }
                    }
                    foreach ($available_extras[$type] as $extras_id => $extras_item) {
                        if (!isset(self::$sort[$extras_id])) {
                            self::$sort[$extras_id] = ++$count;
                        }
                        if (!empty($installed[$app_id][$type][$extras_id])) {
                            $_installed = $installed[$app_id][$type][$extras_id];
                            if (!empty($extras_item['inherited'])) {

                                foreach ($extras_item['inherited'] as $inherited_app_id => $inherited) {

                                    $inherited_extras_id = preg_replace('@(^|^.+/)([^/]+)$@', '$2', $inherited['slug']);
                                    if (empty($installed[$inherited_app_id][$type][$inherited_extras_id])) {
                                        $_installed = array(
                                            'id'        => $extras_id,
                                            'slug'      => $app_id.'/'.$type.'/'.$extras_id,
                                            'vendor'    => $vendor,
                                            'installed' => null,
                                        );
                                        break;
                                    }
                                }

                            }
                            $extras[$app_id][$type][$extras_id] = array_merge($_installed, $extras_item);
                        } else {
                            $extras_item += array(
                                'id'        => $extras_id,
                                'slug'      => $app_id.'/'.$type.'/'.$extras_id,
                                'vendor'    => $vendor,
                                'installed' => null,
                            );
                            $extras[$app_id][$type][$extras_id] = $extras_item;
                        }
                    }
                }

                foreach ($extras as &$available_extras) {
                    uksort($available_extras[$type], array(__CLASS__, 'sort'));
                    unset($available_extras);
                }
            }
        }

        return $extras;
    }

    private static function sort($a, $b)
    {
        static $min = 0;
        if (!isset(self::$sort[$a])) {
            self::$sort[$a] = --$min;
        }
        if (!isset(self::$sort[$b])) {
            self::$sort[$b] = --$min;
        }
        return max(-1, min(1, (self::$sort[$a] - self::$sort[$b])));
    }

    /**
     * @since 2.0
     * @param $slug
     * @param array $options
     * @throws Exception
     * @return array();
     */
    public function getItemInfo($slug, $options = array())
    {
        $info = null;
        $id = preg_replace('@/.*$@', '', $slug);

        if (empty($options['local'])) {
            switch ($id) {
                case 'wa-plugins':
                    $info = $this->query('app/'.$slug.'/');
                    break;
                default:
                    //TODO send installation hash
                    $url = 'app/';
                    if (!empty($options['inherited'])) {
                        $url .= implode(',', (array)$options['inherited']).',';
                    }
                    $url .= $slug;
                    $info = $this->query($url = preg_replace('@,?\\*@', '', $url).'/');
                    break;
            }
        } else {
            $info = array();
        }

        if ($info || !empty($options['local'])) {
            if ($id == '*') {
                $slug = preg_replace('@^\\*/@', 'site/', $slug);
            }
            $exists = !!$info;
            $info += array(
                'installed'  => null,
                'applicable' => null,
                'slug'       => $slug,
            );

            $installed_apps = self::getConfig(self::CONFIG_APPS);
            if (!empty($installed_apps[$id]) || ($id == 'wa-plugins') || ($id == '*')) {
                $filter = array();
                if (!empty($options['vendor'])) {
                    $filter['vendor'] = $options['vendor'];
                }
                $key = ($id == 'wa-plugins') ? '' : 'wa-apps';
                $info = array_merge($info, $this->info($key, $info['slug'], $filter));
                if (!$exists && empty($info['installed'])) {
                    $info = array();
                } elseif (!empty($info['inherited'])) {
                    foreach ($info['inherited'] as $inherited_app_id => $inherited) {
                        $inherited_info = $this->info(($inherited_app_id == 'wa-plugins') ? '' : 'wa-apps', $inherited['slug'], $filter);
                        if (empty($inherited_info['installed'])) {
                            $info['installed'] = false;
                            break;
                        }
                    }
                }
            }

            $this->extend($info, $options);
        }
        return $info;

    }

    /**
     * Fix item images and icons names
     * @param $item
     * @param null $id
     * @return void
     */
    private static function fixItemIcon(&$item, $id = null)
    {
        if (!$id) {
            $id = (isset($item['id']) && $item['id']) ? $item['id'] : $item['slug'];
        }

        if (isset($item['installed']) && !empty($item['installed']) !== false) {
            $l = &$item['installed'];
            $sizes = array(48, 24, 16);
            if (!empty($l['icon']) && !is_array($l['icon'])) {
                $l['icon'] = array(48 => $l['icon']);
            }

            if (!isset($l['img'])) {
                if (!isset($l['icon'])) {
                    $l['img'] = sprintf(self::ITEM_ICON, $id);
                } elseif (isset($l['icon'][reset($sizes)])) {
                    $l['img'] = $l['icon'][reset($sizes)];
                } else {
                    $l['img'] = sprintf(self::ITEM_ICON, $id);
                }
            }
            if (!isset($l['icon'])) {
                $l['icon'] = array();
            }

            foreach ($sizes as $size) {
                if (!isset($l['icon'][$size])) {
                    $l['icon'][$size] = $l['img'];
                }
            }


            foreach ($l['icon'] as &$i) {
                //TODO use ROOT_URL
                $i = '/wa-apps/'.$item['slug'].'/'.$i;
            };
            unset($i);
            if (empty($item['icons'])) {
                $item['icons'] = $l['icon'];
            }
        }

    }

    private function buildUrl(&$path)
    {
        $original = $path;
        if (is_array($path)) {
            $is_url = true;
            foreach ($path as &$chunk) {
                $is_url = $this->buildUrl($chunk) && $is_url;
                unset($chunk);
            }
        } else {
            $is_url = preg_match('@^https?://@', $path);
            if (($this->license || $this->identity_hash || $this->beta) && $is_url && $this->originalUrl($path)) {
                $query = parse_url($path, PHP_URL_QUERY);
                if ($this->license) {
                    $query = $query.($query ? '&' : '').'license='.$this->license;
                }
                if ($this->identity_hash) {
                    $query = $query.($query ? '&' : '').'hash='.$this->identity_hash;
                }
                if ($this->promo_id) {
                    $query = $query.($query ? '&' : '').'promo_id='.$this->promo_id;
                }
                if ($domain = $this->getDomain()) {
                    $query = $query.($query ? '&' : '').'domain='.urlencode(base64_encode($domain));
                }
                if (preg_match('@/(download|archive)/@', $path)) {
                    $query = $query.($query ? '&' : '').'signature='.urlencode(self::getServerSignature());

                    if ($this->beta && preg_match('@/archive/@', $path)) {
                        $path = preg_replace('@/(archive)/@', "/\$1/{$this->beta}/", $path, 1);
                    }
                }

                if (preg_match('@/versions/\?@', $path)) {
                    if ($this->beta) {
                        $path = preg_replace('@/versions/\?@', "/versions/{$this->beta}/?", $path, 1);
                    }
                }

                if (preg_match('@/updates/\?@', $path)) {
                    if ($this->beta) {
                        $path = preg_replace('@/updates/\?@', "/updates/{$this->beta}/?", $path, 1);
                    }
                    parse_str($path, $raw);
                    if (!empty($raw['v']) && ($raw['v'] = array_filter($raw['v']))) {
                        $stack = debug_backtrace();
                        foreach ($stack as $s) {
                            if (isset($s['class']) && isset($s['function'])) {
                                if (md5($s['class'].$s['function']) == '4e78e0ba4240bbbcb818f122201cc5b6') {
                                    $raw['v'] = array();
                                    break;
                                }
                            }
                        }
                        $callback = create_function('$a', 'return strpos($a,"/")===false;');
                        if ($apps = array_filter(array_keys($raw['v']), $callback)) {
                            $query = $query.($query ? '&' : '').self::getDomains($apps);
                        }
                    }
                }

                $path = preg_replace("@\?.*$@", '', $path);
                $path .= '?'.$query;
            }
            if (self::$locale && $is_url) {
                $query = parse_url($path, PHP_URL_QUERY);
                $query .= '&locale='.self::$locale;
                $path = preg_replace("@\?.*$@", '', $path);
                $path .= '?'.$query;
            }
        }
        return $is_url;
    }

    private function originalUrl($url)
    {
        static $original_host;
        if (!$original_host) {
            foreach ((array)$this->sources['apps'] as $vendor => $source) {
                if (!$vendor) {
                    $vendor = self::VENDOR_SELF;
                }
                if ($vendor == self::VENDOR_SELF) {
                    $original_host = parse_url($source, PHP_URL_HOST);
                    $original_host = preg_replace('@(^www\.|:\d+$)@', '', $original_host);
                }
            }
        }
        $host = parse_url($url, PHP_URL_HOST);
        return $original_host && (($original_host == $host) || (strpos($host, '.'.$original_host)));
    }

    /**
     *
     * @todo rename and use corrected names
     * @param array $item
     * @param string $id
     * @param array $fields
     */
    private static function fixItemCurrent(&$item, $id = null, $fields = array())
    {
        if (!$id) {
            $id = empty($item['id']) ? ($item['slug']) : $item['id'];
        }

        $item['action'] = self::ACTION_NONE;
        if (empty($item['requirements'])) {
            $item['requirements'] = array();
        }
        $item['applicable'] = true;

        $fields += array(
            'id',
            'slug',
            'name',
            'description',
            'vendor_name' => 'vendor',
            'system',
            'vendor',
            'commercial',
            'version',
        );

        foreach ($fields as $field => $source) {
            if (is_numeric($field)) {
                $field = $source;
            }
            if (empty($item[$field])) {
                if (!empty($item['installed'][$source])) {
                    $item[$field] = $item['installed'][$source];
                } elseif (!isset($item[$field])) {
                    $item[$field] = '';
                }
            }

        }
        $ml_fileds = array('name', 'description');
        foreach ($ml_fileds as $field) {
            //TODO use _wd/etc
            if (isset($item[$field]) && is_array($item[$field])) {
                $item[$field] = isset($item[$field][self::$locale]) ? $item[$field][self::$locale] : current($item[$field]);
            }
        }

        $remap = array(
            'vendor_name' => 'vendor',
        );
        foreach ($remap as $target => $source) {
            if (empty($item[$target])) {
                $item[$target] = $item[$source];
            }
        }
        if (!preg_match('@^wa-plugins/@', $item['slug'])) {
            self::fixItemIcon($item);
        }
    }

    private static function fixItemVersion(&$item, $id = null, $extras_info = null)
    {
        if ((!isset($item['installed']['version']) || !$item['installed']['version']) && !empty($item['installed'])) {
            $item['installed']['version'] = '0.0.0';
        }

        if (is_null($id) && isset($item['slug'])) {
            $id = $item['slug'];
        }
        if ($id && !empty($item['installed'])) {
            if (empty($item['config_path'])) {
                if (is_null($extras_info)) {
                    $build_path = self::$root_path.sprintf(self::ITEM_BUILD, $id);
                } else {
                    $build_path = self::$root_path.sprintf(self::ITEM_EXTRAS_PATH, $id, $extras_info['subpath']).'build.php';
                }
            } else {
                $build_path = self::$root_path.dirname($item['config_path']).'/build.php';
            }
            if (file_exists($build_path) && ($build = include($build_path))) {
                $item['installed']['version'] .= ".{$build}";
            } elseif (preg_match('/((^|\\.)[\\d]+){3}$/', $item['installed']['version'])) {
                $item['installed']['version'] .= ".0";
            }
        }
    }

    private function sortAppsCallback($a, $b)
    {
        $a['order'] = self::getActionPriority($a['action']);
        $b['order'] = self::getActionPriority($b['action']);
        $result = max(-1, min(1, ($b['order'] - $a['order'])));
        if ($result == 0) {
            //XXX hardcoded order
            if ($a['slug'] == 'installer') {
                $result = 1;
            } elseif ($b['slug'] == 'installer') {
                $result = -1;
            } else {
                if (isset($a['priority']) && isset($b['priority'])) {
                    $result = max(-1, min(1, ($b['priority'] - $a['priority'])));
                }
                if ($result == 0) {
                    $ap = (int)!empty($a['payware']);
                    $bp = (int)!empty($b['payware']);
                    if ($ap != $bp) {
                        $result = $bp - $ap;
                    }
                }
                if ($result == 0) {
                    $result = strcmp($a['name'], $b['name']);
                }
            }
        }
        return $result;
    }

    private static function getActionPriority($action)
    {
        $priority = null;
        switch ($action) {
            case self::ACTION_INSTALL:
                $priority = 5;
                break;
            case self::ACTION_CRITICAL_UPDATE:
                $priority = 4;
                break;
            case self::ACTION_UPDATE:
                $priority = 3;
                break;
            case self::ACTION_REPAIR:
                $priority = 2;
                break;
            case self::ACTION_NONE:
                $priority = 1;
                break;
        }
        return $priority;
    }

    private static function getFolders($path, $pattern = '/^[a-z_\-\d][a-z_\-\d\.]*$/i')
    {
        $paths = array();
        if (file_exists(self::$root_path.$path)) {
            $directoryContent = scandir(self::$root_path.$path);
            foreach ($directoryContent as $item_path) {
                if (preg_match($pattern, $item_path) && is_dir(self::$root_path.$path.'/'.$item_path)) {
                    $paths[$item_path] = true;
                }
            }
        }
        return $paths;
    }

    /**
     * opcache workaround
     * @var array
     */
    private static $configs = array();

    private static function getConfig($path)
    {
        $config = array();
        $ml_fields = array('name', 'description');
        if ($path) {
            $_path = self::$root_path.$path;
            //hack for theme xml
            $path_xml = preg_replace('@\.php$@', '.xml', $_path);
            if (file_exists($path_xml)) {
                if ($xml = @simplexml_load_file($path_xml)) {

                    foreach ($ml_fields as $field) {
                        $config[$field] = array();
                    }
                    foreach ($xml->attributes() as $field => $value) {
                        $config[$field] = (string)$value;
                    }

                    foreach ($ml_fields as $field) {
                        if ($xml->$field) {
                            foreach ($xml->$field as $value) {
                                if ($locale = (string)$value['locale']) {
                                    $config[$field][$locale] = (string)$value;
                                }
                            }
                        }
                    }
                }
            } elseif (file_exists($_path)) {
                if (!isset(self::$configs[$path])) {
                    $config = include($_path);
                    if (!is_array($config)) {
                        $config = array();
                    }
                    self::$configs[$path] = $config;
                } else {
                    $config = self::$configs[$path];
                }
            }
        }
        foreach ($ml_fields as $field) {
            if (isset($config[$field]) && is_array($config[$field])) {
                $key = array_intersect(array(self::$locale, 'en_US',), array_keys($config[$field]));
                $config[$field] = $key ? $config[$field][reset($key)] : reset($config[$field]);
            }
        }
        return $config;
    }

    private static function setConfig($path, $config)
    {
        if (!(is_array($config))) {
            throw new Exception('Invalid config');
        }
        if (!self::mkdir(dirname($path))) {
            throw new Exception('Error make path '.$path);
        }
        $fp = @fopen(self::$root_path.$path, 'w');
        if (!$fp) {
            throw new Exception('Error while save config at '.$path);
        }
        if (!@flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new Exception('Unable to lock config file '.$path);
        }
        fwrite($fp, "<?php\n\nreturn ");
        fwrite($fp, var_export($config, true));
        fwrite($fp, ";\n//EOF");

        @fflush($fp);
        @flock($fp, LOCK_UN);
        @fclose($fp);
        self::$configs[$path] = $config;
        return $config;
    }

    /**
     *
     *
     * @param $app_id string
     * @param $enabled boolean or null to remove
     * @param array $routing
     * @return bool prev app state
     */
    public function updateAppConfig($app_id, $enabled = true, $routing = array())
    {
        $config = self::getConfig(self::CONFIG_APPS);
        $current = isset($config[$app_id]) ? $config[$app_id] : null;
        if ($enabled === null) {
            if (isset($config[$app_id])) {
                unset($config[$app_id]);
            }
        } else {
            $config[$app_id] = $enabled;
        }
        if (!$enabled) {
            $this->updateRoutingConfig($app_id, false);
        } elseif ($routing) {
            $this->updateRoutingConfig($app_id, $routing);
        }
        self::setConfig(self::CONFIG_APPS, $config);
        return $current;
    }

    /**
     *
     * @throws Exception
     * @param $app_id string
     * @param $plugin_id string
     * @param $enabled boolean or null to remove
     * @return array
     */
    public function updateAppPluginsConfig($app_id, $plugin_id, $enabled = true)
    {
        $config = array($plugin_id => $enabled);
        $path = sprintf(self::CONFIG_APP_PLUGINS, $app_id);
        $config = array_merge(self::getConfig($path), $config);
        if (is_null($enabled)) {
            unset($config[$plugin_id]);
        }
        return self::setConfig($path, $config);
    }

    private function setRoutingConfig($app_id, $theme_id)
    {
        $changed = false;
        $routing = self::getConfig(self::CONFIG_ROUTING);
        foreach ($routing as & $routes) {
            foreach ($routes as &$route) {
                if (is_array($route)) { //route is array
                    if (isset($route['app']) && ($route['app'] == $app_id)) {
                        if (empty($route['theme'])) {
                            $route['theme'] = $theme_id;
                            $changed = true;
                        }
                        if (empty($route['theme_mobile'])) {
                            $route['theme_mobile'] = $theme_id;
                            $changed = true;
                        }
                    }
                } else { //route is string
                    $route_app = array_shift(array_filter(explode('/', $route), 'strlen'));
                    if ($route_app == $app_id) {

                    }
                }
                unset($route);
            }
            unset($routes);
        }
        if ($changed) {
            self::setConfig(self::CONFIG_ROUTING, $routing);
        }
        return $changed;
    }

    /**
     *
     * @throws Exception
     * @param $app_id string
     * @param $routing array
     * @param $domain string
     * @return string
     */
    private function updateRoutingConfig($app_id = 'default', $routing = array(), $domain = null)
    {
        $result = null;
        $current_routing = self::getConfig(self::CONFIG_ROUTING);
        if (!$routing) {
            foreach ($current_routing as $domain => & $routes) {
                foreach ($routes as $route_id => $route) {
                    if (is_array($route)) { //route is array
                        if (isset($route['app']) && ($route['app'] == $app_id)) {
                            unset($routes[$route_id]);
                        }
                    } else { //route is string
                        $route = array_shift(array_filter(explode('/', $route), 'strlen'));
                        if ($route == $app_id) {
                            unset($routes[$route_id]);
                        }
                    }
                }
                unset($routes);
            }
        } else {
            if (is_null($domain)) {
                $domain = $_SERVER['HTTP_HOST'];
                if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) {
                    $root_url = $_SERVER['SCRIPT_NAME'];
                } elseif (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF']) {
                    $root_url = $_SERVER['PHP_SELF'];
                } else {
                    $root_url = '/';
                }
                $root_url = preg_replace('!/[^/]*$!', '/', $root_url);
                $root_url = trim($root_url, '/');
                if ($root_url) {
                    $domain .= '/'.$root_url;
                }
            }

            if (!isset($current_routing[$domain])) {
                $current_routing[$domain] = array();
            }

            $root_owned = false;
            foreach ($current_routing[$domain] as $route) {
                $url = is_array($route) ? $route['url'] : $route;
                if (strpos($url, '*') === 0) {
                    $root_owned = true;
                    break;
                }
            }
            if (($app_id == 'site')) {
                $routing['url'] = $root_owned ? "{$app_id}/*" : '*';
            }

            $rule_exists = false;
            foreach ($current_routing[$domain] as $route) {
                if (is_array($route)) { //route is array
                    if (isset($route['app']) && ($route['app'] == $app_id)) {
                        $rule_exists = true;
                        break;
                    }
                } else { //route is string
                    $route = array_shift(array_filter(explode('/', $route), 'strlen'));
                    if ($route == $app_id) {
                        $rule_exists = true;
                    }
                }
            }

            if (!$rule_exists) {
                if ($root_owned) {
                    array_unshift($current_routing[$domain], $routing);
                } else {
                    $current_routing[$domain][] = $routing;
                }
            }
        }
        self::setConfig(self::CONFIG_ROUTING, $current_routing);
        return $domain;
    }

    /**
     * Update database settings
     * @throws Exception
     * @param $config array
     * @param $id
     * @return void
     */
    public function updateDbConfig($config = array(), $id = 'default')
    {
        $config = array($id => $config);
        $config = array_merge(self::getConfig(self::CONFIG_DB), $config);
        self::setConfig(self::CONFIG_DB, $config);
    }

    /**
     * Update database settings
     *
     * @param $config array
     * @internal param $id
     * @return void
     */
    private static function updateGenericConfig($config = array())
    {
        $default = array(
            'debug'         => false,
            'identity_hash' => md5(__FILE__.(function_exists('php_uname') ? php_uname() : '').phpversion().rand(0, time())),
        );
        $config = array_merge($default, self::getConfig(self::CONFIG_GENERIC), $config);
        self::setConfig(self::CONFIG_GENERIC, $config);
    }

    /**
     *
     * Setup generic options
     * @param array $options
     * @return void
     */
    public static function setGenericOptions($options = array())
    {
        self::init();
        $allowed_options = array(
            'mod_rewrite',
            'debug',
            'license_key',
        );
        foreach ($options as $id => $option) {
            if (!in_array($id, $allowed_options, true)) {
                unset($options[$id]);
            }
        }
        if ($options) {
            self::updateGenericConfig($options);
        }
    }

    /**
     * Register applications at config and add routing for it
     *
     * @param $slug
     * @param $domain string domain ID fo
     * @param bool|string $edition string application edition
     * @throws Exception
     * @internal param string $app_id application slug
     * @return void
     */
    public function installWebAsystItem($slug, $domain = null, $edition = true)
    {
        $slugs = explode('/', $slug);
        if (count($slugs) == 3) {
            switch ($slugs[1]) {
                case 'plugins':
                    $this->updateAppPluginsConfig($slugs[0], $slugs[2]);
                    break;
                case 'themes':
                    $this->setRoutingConfig($slugs[0], $slugs[2]);
                    break;
                default:
                    throw new Exception("Invalid subject for method ".__METHOD__);
            }
        } elseif (!preg_match('@^wa-plugins/@', $slug)) {
            $this->installWebAsystApp($slug, $domain, $edition);
        }
    }

    /**
     * Register applications at config and add routing for it
     *
     * @param $app_id string application slug
     * @param $domain string domain ID fo
     * @param bool|string $edition string application edition
     * @return void
     */
    public function installWebAsystApp($app_id, $domain = null, $edition = true)
    {
        $prev = $this->updateAppConfig($app_id, $edition);
        $config = self::getConfig(sprintf(self::ITEM_CONFIG, $app_id));
        if (!$prev && !empty($config['frontend'])) {
            $routing = array(
                'url'    => $app_id.'/*',
                'app'    => $app_id,
                'locale' => self::$locale,
            );
            if (!empty($config['routing_params']) && is_array($config['routing_params'])) {
                $routing = array_merge($routing, $config['routing_params']);
            }
            $domain = $this->updateRoutingConfig($app_id, $routing, $domain);
            $this->addAppRobots($app_id, $routing, $domain);
        }
    }

    private function addAppRobots($app_id, $routing, $domain)
    {

        $path = self::$root_path.sprintf(self::ITEM_ROBOTS, $app_id);
        if (file_exists($path)) {
            $app_raw_robots = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $app_robots = array();
            $user_agent = false;
            foreach ($app_raw_robots as $str) {
                $str = trim($str);
                if ($str[1] == '#') {
                    continue;
                }
                $str = explode(':', $str, 2);
                if (strtolower(trim($str[0])) == 'user-agent') {
                    $user_agent = trim($str[1]);
                } else {
                    $app_robots[$user_agent][] = array_map('trim', $str);
                }
            }

            $domain = $domain.'/'.str_replace('/?', '/', preg_replace('/\.?\*$/i', '', $routing['url']));
            $url = preg_replace('@^[^/]+/?@', '/', $domain);
            $domain = preg_replace('@/.*$@', '/', $domain);

            $robots = array();
            foreach ($app_robots as $user_agent => $rows) {
                $robots[] = 'User-agent: '.$user_agent."\n";
                $robots[] = "# wa ".$app_id." ".$routing['url']."\n";
                foreach ($rows as $row) {
                    if (strpos($row[1], '[URL]') !== false) {
                        $row[1] = str_replace('[URL]', $url, $row[1]);
                    }
                    $robots[] .= $row[0].": ".$row[1]."\n";
                }
                $robots[] .= "# wa ".$app_id."\n";
                $robots[] = "\n";
            }
            $robots_path = "wa-data/public/site/data/{$domain}/robots.txt";
            self::mkdir("wa-data/public/site/data/{$domain}");
            if ($fp = fopen(self::$root_path.$robots_path, 'a')) {
                fwrite($fp, "\n".implode("", $robots));
                fclose($fp);
            }
        }
    }

    private static function mkdir($target_path, $mode = 0777)
    {
        if (!file_exists(self::$root_path.$target_path)) {
            if (!mkdir(self::$root_path.$target_path, $mode & 0777, true)) {
                throw new Exception("Error occurred while creating a directory {$target_path}");
            }
        } elseif (!is_dir(self::$root_path.$target_path)) {
            throw new Exception("Error occurred while creating a directory {$target_path} - it's a file");

        } elseif (!is_writable(self::$root_path.$target_path)) {
            throw new Exception("Directory {$target_path} unwritable");
        }
        if (preg_match('`^/?(wa-data/protected|wa-log|wa-cache|wa-config)/?`', $target_path, $matches)) {
            $htaccess_path = $matches[1].'/.htaccess';
            if (!file_exists(self::$root_path.$htaccess_path)) {
                if ($fp = @fopen(self::$root_path.$htaccess_path, 'w')) {
                    fwrite($fp, "Deny from all\n");
                    fclose($fp);
                } else {
                    throw new Exception("Error while trying to protect a directory {$target_path} with htaccess");
                }
            }
        }
        return true;
    }

    /**
     *
     * Get applicable action
     * @param array $item
     * @return string
     */
    private static function applicableAction($item)
    {
        if (!empty($item['installed'])) {
            if (!empty($item['installed']['version'])) {
                if (isset($item['vendor']) && isset($item['installed']['vendor']) && ($item['vendor'] != $item['installed']['vendor'])) {
                    $action = self::ACTION_INSTALL;
                } elseif (isset($item['edition']) && ($item['edition'] != $item['installed']['edition'])) {
                    $action = self::ACTION_INSTALL;
                } elseif (version_compare($item['version'], $item['installed']['version'], '>')) {
                    if (isset($item['critical']) && version_compare($item['critical'], $item['installed']['version'], '>')) {
                        $action = self::ACTION_CRITICAL_UPDATE;
                    } else {
                        $action = self::ACTION_UPDATE;
                    }
                } else {
                    $action = self::ACTION_REPAIR;
                }
            } else {
                $action = self::ACTION_UPDATE;
            }
        } elseif (!empty($item['download_url'])) {
            $action = self::ACTION_INSTALL;
        } else {
            $action = self::ACTION_NONE;
        }
        return $action;
    }

    /**
     *
     * Query to updates server
     * @since 2.0
     * @param string $query
     * @param string $vendor
     * @param boolean $values return simple array or with keys
     * @return bool|mixed|string
     */
    private function query($query, $vendor = self::VENDOR_SELF, $values = false)
    {
        /**
         * @var $file waInstallerFile
         */
        static $file;
        $result = false;
        $sources = $this->getSources(self::LIST_APPS, $vendor);
        if (!empty($sources[$vendor])) {
            /**
             * @todo temporal hack with replace
             */
            $url = preg_replace('@apps/list/$@', '2.0/', $sources[$vendor]).$query;
            if ($this->buildUrl($url)) {
                if (!$file) {
                    $file = new waInstallerFile();
                }
                $result = $file->getData($url, 'json', $values);
            }
        }
        return $result;
    }

    /**
     *
     * Verify that updates allowed
     * @throws Exception
     */
    public function checkUpdates()
    {
        if (!$this->sources) {
            throw new Exception('Empty sources list');
        }
        $requirements = array();
        $requirements['php.curl'] = array(
            'description' => 'Get updates information from update servers',
            'strict'      => true,
        );
        $requirements['phpini.allow_url_fopen'] = array(
            'description' => 'Get updates information from update servers',
            'strict'      => true,
            'value'       => 1,
        );
        $requirements['rights'] = array(
            'subject' => '.',
            'strict'  => true,
            'value'   => true,
        );
        $messages = array();
        if (!self::checkRequirements($requirements)) {
            foreach ($requirements as $requirement) {
                if (!$requirement['passed']) {
                    $messages[] = $requirement['name'].' '.$requirement['warning'];
                } else {
                    $messages = array();
                    break;
                }
            }
            if ($messages) {
                throw new Exception(implode("\n", $messages));
            }
        }
    }
}
//EOF