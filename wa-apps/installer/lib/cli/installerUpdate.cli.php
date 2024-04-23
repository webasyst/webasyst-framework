<?php
/**
 * Usage:
 * php cli.php installer update check
 * php cli.php installer update <slug>
 * php cli.php installer update everything
 */
class installerUpdateCli extends waCliController
{
    protected $stderr;

    protected function preExecute()
    {
        $this->stderr = fopen('php://stderr', 'w');
        $this->setupInstallationDomain();
    }

    protected function postExecute()
    {
        fclose($this->stderr);
    }

    public function execute()
    {
        try {
            $this->unsafeExecute();
        } catch (Throwable $e) {
            fwrite($this->stderr, "ERROR\n".((string)$e));
        }
    }

    public function unsafeExecute()
    {
        $update_check = $update_everything = false;
        $update_list = waRequest::param();
        switch (ifset($update_list, 0, '')) {
            case 'everything':
                $update_everything = true;
                $update_list = [];
                break;
            case 'list':
                array_shift($update_list);
                if (!$update_list) {
                    return $this->usage();
                }
                break;
            case 'check':
                $update_check = true;
                $update_list = [];
                break;
            default:
                return $this->usage();
        }

        $items = installerHelper::getUpdates();
        list($items, $inapplicable_count, $applicable_slugs) = $this->filterInapplicable($items);
        if ($update_check) {
            echo join(PHP_EOL, $applicable_slugs);
            return;
        }

        $urls = $this->prepareInstallationUrls($items);

        $this->executeUpdate($urls);

        $items = installerHelper::getUpdates();
        echo "PENDING ".$inapplicable_count."\n";
    }

    protected function usage()
    {

        fwrite($this->stderr, "Usage:
> php cli.php installer update check
team
shop
shop/plugin/brands
shop/theme/hypermarket

> php cli.php installer update shop shop/plugin/brands shop/theme/hypermarket
UPDATED 3
PENDING 1

> php cli.php installer update everything
UPDATED 1
PENDING 0
");
    }

    protected function setupInstallationDomain()
    {
        $domain = getenv('WA_INSTALLATION_DOMAIN');
        if (!$domain) {
            $sql = "
                SELECT cs.value
                FROM wa_contact_settings AS cs
                    JOIN wa_contact AS c
                        ON c.id=cs.contact_id
                WHERE c.is_user >= 1
                    AND cs.app_id='webasyst'
                    AND cs.name='backend_url'
                ORDER BY c.last_datetime DESC
                LIMIT 1
            ";
            $backend_url = (new waModel())->query($sql)->fetchField();
            if ($backend_url) {
                $domain = parse_url($backend_url, PHP_URL_HOST);
            }
        }

        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = $domain;
    }

    protected function filterInapplicable($items)
    {
        $execute_actions = array(
            waInstallerApps::ACTION_INSTALL, // update may be ACTION_INSTALL if vendor changed or licence type changed (normal -> premium)
            waInstallerApps::ACTION_CRITICAL_UPDATE,
            waInstallerApps::ACTION_UPDATE,
        );

        $applicable_slugs = [];
        $inapplicable_count = 0;
        $checkItem = function(&$info) use ($execute_actions, &$applicable_slugs, &$inapplicable_count) {
            if (!in_array($info['action'], $execute_actions)
              || empty($info['applicable'])
              || (!empty($info['commercial']) && empty($info['purchased']))
            ) {
                $inapplicable_count++;
                return false;
            } else {
                $applicable_slugs[] = $info['slug'];
                return true;
            }
        };

        foreach ($items as $app_id => &$info) {
            if (!empty($info['download_url'])) {
                if (!$checkItem($info)) {
                    unset($info['download_url']);
                }
            }

            foreach (array('themes', 'plugins', 'widgets') as $type) {
                if (empty($info[$type]) || !is_array($info[$type])) {
                    unset($info[$type]);
                    continue;
                }

                foreach ($info[$type] as $extra_id => $extras_info) {
                    if (empty($extras_info['download_url']) || !$checkItem($extras_info)) {
                        unset($info[$type][$extra_id]);
                    }
                }
            }
        }
        unset($info);

        return [$items, $inapplicable_count, $applicable_slugs];
    }

    protected function prepareInstallationUrls($items)
    {
        $urls = [];
        $add = function($target, $info, $item_id = null) use (&$urls) {
            $urls[$target] = array(
                'source'    => $info['download_url'],
                'target'    => $target,
                'slug'      => $target,
                'real_slug' => $info['slug'],
                'md5'       => !empty($info['md5']) ? $info['md5'] : null,
            );

            if ($item_id) {
                $urls[$target] = array_merge($urls[$target], array(
                    'slug'      => $item_id,
                    'real_slug' => $info['slug'],
                    'pass'      => false,
                    'name'      => $info['name'],
                    'icon'      => $info['icon'],
                    'update'    => !empty($info['installed']),
                    'subject'   => empty($info['subject']) ? 'system' : $info['subject'],
                    'edition'   => empty($info['edition']) ? true : $info['edition'],
                ));
            }
        };

        foreach ($items as $app_id => $info) {
            if (!empty($info['download_url'])) {
                $info['subject'] = 'app';
                if ($app_id == 'installer') {
                    foreach ($info['download_url'] as $target => $url) {
                        $_info = $info;
                        $_info['download_url'] = $url;
                        $_info['name'] = _w('Webasyst framework').' ('.$target.')';
                        $add($target, $_info);
                        unset($_info);
                    }
                } else {
                    $target = 'wa-apps/'.$app_id;
                    $add($target, $info, $app_id);
                }
            }

            foreach (array('themes', 'plugins', 'widgets') as $type) {
                foreach (ifset($info, $type, []) as $extra_id => $extras_info) {
                    $extras_info['subject'] = 'app_'.$type;
                    if ($type == 'themes' && is_array($extras_info['download_url'])) {
                        foreach ($extras_info['download_url'] as $target => $url) {
                            $__info = $extras_info;
                            $__info['download_url'] = $url;
                            $__info['slug'] = preg_replace('@^wa-apps/@', '', $target);
                            $__info['app'] = preg_replace('@^wa-apps/([^/]+)/.+$@', '$1', $target);

                            if (!isset($urls[$target])) {
                                if (($__info['app'] == $app_id) || empty($items[$__info['app']]['themes'][$extra_id])) {
                                    if (!empty($items[$__info['app']]['themes'][$extra_id]['name'])) {
                                        $__info['name'] .= " ({$info['name']})";
                                    } elseif ( ( $app_info = wa()->getAppInfo($__info['app']))) {
                                        $__info['name'] .= " ({$app_info['name']})";
                                    } else {
                                        $__info['name'] .= " ({$__info['app']})";
                                    }
                                    $add($target, $__info);
                                }
                            }
                        }
                    } else {
                        if (!empty($info['name'])) {
                            $extras_info['name'] .= " ({$info['name']})";
                        }
                        if (strpos($app_id, '/')) {
                            //system plugins
                            $target = $app_id.'/'.$extra_id;
                        } elseif (($app_id == 'webasyst') && ($type == 'widgets')) {
                            $target = 'wa-widgets/'.$extra_id;
                        } elseif (($app_id == 'wa-widgets')) {
                            $target = 'wa-widgets/'.$extra_id;
                        } else {
                            $target = 'wa-apps/'.$app_id.'/'.$type.'/'.$extra_id;
                        }
                        $add($target, $extras_info, $target);
                    }
                }
            }
            unset($info);
        }

        return $urls;
    }

    protected function logError($message)
    {
        waLog::log($message, 'installer.cli.update.log');
    }

    protected function executeUpdate($urls)
    {
        $updater = new waInstaller(waInstaller::LOG_WARNING);
        $app_settings_model = new waAppSettingsModel();
        $updater->init();
        $app_settings_model->ping();

        $urls = $updater->update($urls);
        $app_settings_model->ping();

        $updated_urls = [];
        $updated_slugs = [];
        foreach ($urls as $item) {
            if (empty($item['skipped'])) {
                $updated_urls[] = $item;
                if (!empty($item['real_slug'])) {
                    $updated_slugs[] = $item['real_slug'];
                }
            }
        }
        echo "UPDATED ".count($updated_urls)."\n";
        if (empty($updated_urls)) {
            return;
        }
        if (!empty($updated_slugs)) {
            $sender = new installerUpdateFact(installerUpdateFact::ACTION_ADD, $updated_slugs);
            $sender->query();
        }

        //update themes
        foreach ($updated_urls as $url) {
            if (preg_match('@(wa-apps/)?(.+)/themes/(.+)@', $url['slug'], $matches)) {
                try {
                    $theme = new waTheme($matches[3], $matches[2]);
                    $theme->update();
                } catch (Exception $ex) {
                    $this->logError(sprintf('Error during theme %s@%s update: %s', $matches[3], $matches[2], $ex->getMessage())."\n".$e->getTraceAsString());
                }
            }
        }

        $app_settings_model->ping();
        installerHelper::flushCache();
        $app_settings_model->ping();

        wa('installer')->event('end_installation', $urls);
    }

}
