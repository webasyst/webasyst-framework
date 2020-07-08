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
 * @package installer
 */

class installerConfig extends waAppConfig
{
    const ANNOUNCE_CACHE_TTL = 3600; // sec

    const INIT_DATA_CACHE_TTL = 10800; // 3 hours
    const INIT_DATA_CACHE_TTL_DEBUG = 900; // 15 mins

    protected $application_config = array();

    public function init()
    {
        parent::init();
        require_once($this->getPath('installer', 'lib/init'));
    }

    public function getInfo($name = null)
    {
        if ($name == 'csrf' && preg_match("~(\/installer\/requirements\/)$~", waRequest::server('REQUEST_URI'))) {
            return false;
        }

        return parent::getInfo($name);
    }

    public function onCount()
    {
        $args = func_get_args();
        $force = array_shift($args);

        $model = new waAppSettingsModel();
        $app_id = $this->getApplication();
        $count = null;

        //check cache expiration time
        if ($force || ((time() - $model->get($app_id, 'update_counter_timestamp', 0)) > 600) || is_null($count = $model->get($app_id, 'update_counter', null))) {
            $count = installerHelper::getUpdatesCounter('total');
            //check available versions for installed items
            //download if required changelog & requirements for updated items
            //count applicable updates (optional)
            $model->ping();
        } elseif (is_null($count)) {
            $count = $model->get($app_id, 'update_counter');
        }
        if ($count) {
            $count = array(
                'count' => $count,
                'url'   => $url = $this->getBackendUrl(true).$this->application.'/?module=update',
            );
        }
        $this->loadAnnouncements();

        try {
            $this->getTokenData();
        } catch (Exception $e) {}

        return $count;
    }

    public function setCount($n = null)
    {
        wa()->getStorage()->open();
        $model = new waAppSettingsModel();
        $model->ping();
        $app_id = $this->getApplication();
        $model->set($app_id, 'update_counter', $n);
        $model->set($app_id, 'update_counter_timestamp', ($n === false) ? 0 : time());
        parent::setCount($n);
    }

    public function explainLogs($logs)
    {
        $logs = parent::explainLogs($logs);

        if ($logs) {
            $app_url = wa()->getConfig()->getBackendUrl(true).$this->getApplication().'/';

            $actions = array(
                'item_install'   => array(
                    'apps'    => _wd($this->application, 'App %s installed'),
                    'plugins' => _wd($this->application, 'Plugin %s installed'),
                    'widgets' => _wd($this->application, 'Widget %s installed'),
                    'themes'  => _wd($this->application, 'Theme %s installed'),
                ),
                'item_update'    => array(
                    'apps'    => _wd($this->application, 'App %s updated'),
                    'plugins' => _wd($this->application, 'Plugin %s updated'),
                    'widgets' => _wd($this->application, 'Widget %s updated'),
                    'themes'  => _wd($this->application, 'Theme %s updated'),
                ),
                'item_enable'    => array(
                    'apps'    => _wd($this->application, 'App %s enabled'),
                    'plugins' => _wd($this->application, 'Plugin %s enabled'),
                    'widgets' => _wd($this->application, 'Widget %s enabled'),
                    'themes'  => _wd($this->application, 'Theme %s enabled'),
                ),
                'item_disable'   => array(
                    'apps'    => _wd($this->application, 'App %s disabled'),
                    'plugins' => _wd($this->application, 'Plugin %s disabled'),
                    'widgets' => _wd($this->application, 'Widget %s disabled'),
                    'themes'  => _wd($this->application, 'Theme %s disabled'),
                ),
                'item_uninstall' => array(
                    'apps'    => _wd($this->application, 'App %s uninstalled'),
                    'plugins' => _wd($this->application, 'Plugin %s uninstalled'),
                    'widgets' => _wd($this->application, 'Widget %s uninstalled'),
                    'themes'  => _wd($this->application, 'Theme %s uninstalled'),
                ),
            );

            foreach ($logs as $l_id => &$l) {
                $l['params_html'] = '';
                if (isset($actions[$l['action']]) && $l['params']) {
                    $p = json_decode($l['params'], true);

                    if (isset($actions[$l['action']][$p['type']])) {
                        if ($p['type'] == 'themes') {
                            $url = sprintf('%s#/%s/%s/', $app_url, $p['type'], preg_replace('@^.+?/@', '', $p['id']));
                        } else {
                            $url = sprintf('%s#/%s/%s/', $app_url, $p['type'], $p['id']);
                        }
                        $name = sprintf('<a href="%s">%s</a>', $url, $p['id']);
                        $l['params_html'] .= sprintf($actions[$l['action']][$p['type']], $name);
                    }
                }
                unset($l);
            }
        }
        return $logs;
    }

    /**
     * Load data from remote Update server, required to initialize the Installer app.
     * Received data is recommended to be cached. For example using waFunctionCache (see method getInitData).
     *
     * The method is public to have is_callable status.
     *
     * @param string $locale
     * @return array
     * @throws Exception
     */
    public function loadInitData($locale)
    {
        $net_options = array(
            'timeout' => 7,
            'format'  => waNet::FORMAT_JSON,
        );
        $net = new waNet($net_options);

        $wa_installer = installerHelper::getInstaller();

        $init_url_params = array(
            'locale' => $locale,
        );

        $init_url = $wa_installer->getInstallerInitUrl();
        $init_url .= '?'.http_build_query($init_url_params);
        return $net->query($init_url);
    }

    /**
     * Get the data you need to run the application. Any request is cached for 5 minutes.
     *
     * For various reasons, the data may not be returned. This should not be forgotten.
     *
     * @param null|string $locale
     * @return array
     * @throws Exception
     */
    public function getInitData($locale = null)
    {
        if (!$locale) {
            $locale = wa()->getLocale();
        }

        $function_cache = new waFunctionCache(array($this, 'loadInitData'), array(
            'call_limit' => 1, // Cache all requests
            'namespace'  => 'installer/init_data',
            'ttl'        => wa()->getConfig()->isDebug() ? self::INIT_DATA_CACHE_TTL_DEBUG : self::INIT_DATA_CACHE_TTL,
            'hard_clean' => true,
            'hash_salt'  => $locale,
        ));

        return $function_cache->call($locale);
    }

    /**
     * Load token from remote Update server, required to initialize the Installer app.
     * Received data is recommended to be cached. For example using waFunctionCache (see method getToken).
     *
     * @return array
     * @throws Exception
     */
    protected function loadTokenData()
    {
        $net_options = array(
            'timeout' => 7,
            'format'  => waNet::FORMAT_JSON,
        );
        $net = new waNet($net_options);

        $wa_installer = installerHelper::getInstaller();

        $init_url_params = array(
            'hash'   => $wa_installer->getHash(),
            'domain' => waRequest::server('HTTP_HOST'),
        );

        $init_url = $wa_installer->getInstallerTokenUrl();
        $init_url .= '?'.http_build_query($init_url_params);
        $res = $net->query($init_url);

        if (!empty($res['token'])) {
            // Save the last received token in the app settings
            $token_data = array('token' => $res['token']['key'], 'expire_datetime' => $res['token']['expire_datetime']);
            $asm = new waAppSettingsModel();
            $app_id = $this->getApplication();
            $asm->set($app_id, 'token_data', json_encode($token_data));
        }

        return $res;
    }

    /**
     * The method of caching and receiving the installation token
     * for working with a remote Store.
     *
     * @param bool $actual Pass true to request a token from a remote Update server, not dependent on expire_datetime in the cache.
     * @return null|array
     * @throws Exception
     */
    public function getTokenData($actual = false)
    {
        $cache = new waVarExportCache('token', -1, $this->getApplication());

        $cached_token = $cache->get();
        if ($actual || empty($cached_token) || time() >= strtotime(ifempty($cached_token, 'expire_datetime', null))) {

            $new_token = $this->loadTokenData();
            if (empty($new_token['token'])) {
                throw new Exception('Failed to get token from remote Update server');
            }

            $token_data = array(
                'token'                  => $new_token['token']['key'],
                'expire_datetime'        => date('Y-m-d H:i:s', time() + $new_token['token']['expire_timestamp']),
                'inst_id'                => $new_token['token']['inst_id'],
                'sign'                   => $new_token['token']['sign'],
                'remote_expire_datetime' => $new_token['token']['expire_datetime'],
            );
            $cache->set($token_data);
        }

        return $cache->get();
    }

    protected function loadAnnouncements()
    {
        $cache = new waVarExportCache('announcements', self::ANNOUNCE_CACHE_TTL, 'installer');
        if ($cache->isCached() && ($res = $cache->get())) {
            return;
        }

        $net_options = array(
            'timeout' => 7,
            'format'  => waNet::FORMAT_JSON,
        );
        $net = new waNet($net_options);

        $wa_installer = installerHelper::getInstaller();

        $url_params = array(
            'hash'   => $wa_installer->getHash(),
            'domain' => waRequest::server('HTTP_HOST'),
            'locale' => wa()->getLocale(),
        );

        $init_url = $wa_installer->getInstallerAnnounceUrl();
        $init_url .= '?'.http_build_query($url_params);

        $res = $net->query($init_url);
        if (!$res || !array_key_exists('data', $res)) {
            return;
        }

        $cache->set($res);

        $wasm = new waAppSettingsModel();

        if (!$res['data']) {
            $wcsm = new waContactSettingsModel();
            $wasm->exec("DELETE FROM {$wasm->getTableName()} WHERE app_id = 'installer' AND name LIKE 'a-%'");
            $wcsm->exec("DELETE FROM {$wcsm->getTableName()} WHERE app_id = 'installer' AND name LIKE 'a-%'");
            return;
        }
        $ads = (array)$res['data'];

        $old_announcements = $wasm->select('name, value')->where("app_id='installer' AND name LIKE 'a-%'")->fetchAll('name', true);
        $old_keys = array_keys($old_announcements);
        $new_keys = array_keys($ads);

        $ins = array_diff($new_keys, $old_keys);
        $del = array_diff($old_keys, $new_keys);

        if ($ins) {
            $params = array();
            foreach ($ins as $key) {
                $params[] = array(
                    'app_id' => 'installer',
                    'name'   => $key,
                    'value'  => $ads[$key],
                );
            }
            $wasm->multipleInsert($params);
        }

        if ($del) {
            $wasm->exec("DELETE FROM {$wasm->getTableName()} WHERE app_id = 'installer' AND name IN('"
                .join("','", $wasm->escape($del))."')");

            $wcsm = new waContactSettingsModel();
            $wcsm->exec("DELETE FROM {$wcsm->getTableName()} WHERE app_id = 'installer' AND name IN('"
                .join("','", $wcsm->escape($del))."')");
        }
    }
}
