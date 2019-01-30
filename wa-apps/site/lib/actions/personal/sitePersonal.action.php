<?php

class sitePersonalAction extends waViewAction
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        $apps_enabled = array();
        $apps_disabled = array();
        $apps_other = array();

        $apps = wa()->getApps();

        $routes = wa()->getRouting()->getRoutes($domain);
        $apps_routed = array();
        foreach ($routes as $r) {
            if (!empty($r['app'])) {
                $apps_routed[$r['app']] = true;
                wa()->getRouting()->setRoute($r);
                $link = '';
                $apps[$r['app']]['items'] = $this->getItems($r['app'], $link);
                $link = 'http://'.$domain.'/'.substr($link, strlen(wa()->getRootUrl()));
                $apps[$r['app']]['link'] = $link;
                if (!$apps[$r['app']]['items']) {
                    unset($apps[$r['app']]);
                }
            }
        }

        foreach ($apps as $app_id => $app) {
            if (empty($app['my_account'])) {
                unset($apps[$app_id]);
            } else {
                $apps[$app_id]['items'] = $this->getItems($app_id);
                if (!$apps[$app_id]['items']) {
                    unset($apps[$app_id]);
                }
            }
        }

        if (!empty($domain_config['personal'])) {
            foreach ($domain_config['personal'] as $app_id => $enabled) {
                if (isset($apps[$app_id]) && isset($apps_routed[$app_id])) {
                    if ($enabled) {
                        $apps_enabled[$app_id] = $apps[$app_id];
                    } else {
                        $apps_disabled[$app_id] = $apps[$app_id];
                    }
                }
            }
        }

        foreach ($apps as $app_id => $app) {
            if (!isset($apps_disabled[$app_id])) {
                if (isset($apps_routed[$app_id])) {
                    $apps_enabled[$app_id] = $app;
                } else {
                    $apps_other[$app_id] = $app;
                }
            }
        }

        $auth_config = wa()->getAuthConfig($domain);
        if (isset($auth_config['app']) && !empty($apps_disabled[$auth_config['app']])) {
            $this->view->assign('profile_disabled', true);
        }

        $this->view->assign('apps', array(
            'enabled' => $apps_enabled,
            'disabled' => $apps_disabled,
            'other' => $apps_other
        ));

        $this->view->assign(array(
            'domain_id'    => siteHelper::getDomainId(),
            'auth_enabled' => !empty($auth_config['auth']),
            'auth_app'     => ifset($auth_config['app']),
        ));

        $this->template = wa()->getAppPath($this->getTemplate(), 'site');
    }

    protected function getItems($app_id, &$link = null)
    {
        if (!wa()->appExists($app_id)) {
            return array();
        }

        $old_app = wa()->getApp();

        if ($old_app != $app_id) {
            waSystem::getInstance($app_id, null, true);
        }
        $class_name = $app_id.'MyNavAction';
        $result = array();
        if (class_exists($class_name)) {
            /**
             * @var waViewAction $action
             */
            try {
                $action = new $class_name();
                wa()->getView()->assign('my_nav_selected', '');
                $html = $action->display();
                $link = '';
                if (preg_match_all('/<li.*?>(.*?)<\/li>/uis', $html, $match)) {
                    foreach ($match[1] as $m) {
                        if (!$link && preg_match('/href="(.*?)"/uis', $m, $link_m)) {
                            $link = $link_m[1];
                        }
                        $m = preg_replace('#<(script|style).*?>.*?</\\1>#uis', '', $m);
                        $result[] = trim(strip_tags($m));
                    }
                }
            } catch (Exception $e) {
            }
        }

        if ($old_app != $app_id) {
            wa()->setActive($old_app);
        }
        return $result;
    }
}