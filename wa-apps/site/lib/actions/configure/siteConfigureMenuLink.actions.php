<?php
class siteConfigureMenuLinkActions extends waActions
{
    public function saveAppsSelectAction()
    {
        $this->updateDomainConfig(function(&$domain_config) {
            $apps = waRequest::post('apps');
            if ($apps) {
                $domain_config['apps'] = [];
                foreach ($apps as $app) {
                    $domain_config['apps'][] = [
                        'url' => $app['url'],
                        'name' => $app['name']
                    ];
                }
            } else if (isset($domain_config['apps'])) {
                unset($domain_config['apps']);
            }
        });
    }

    public function saveAction()
    {
        $post = waRequest::post();
        if (empty($post['name']) || !trim($post['name'])) {
            $this->displayJson(null, [
                'message' => _w('Enter the link text'),
                'field' => '[name="name"]'
            ]);
            return;
        }

        $data = $this->prepareData($post);

        $link = [
            'name' => $data['name'],
            'url' => ifset($data, 'url', ''),
            'type' => $data['type'],
        ];

        if (!empty($data['target_blank'])) {
            $link['target_blank'] = true;
        }

        if (!empty($data['download'])) {
            $link['download'] = true;
        }

        if (!empty($data['rel'])) {
            $link['rel'] = implode(' ', $data['rel']);
        }

        switch ($link['type']) {
            case siteMenuLinksConfig::PHONE_LINK_TYPE:
                $link['phone'] = trim($data['phone']);
                $link['url'] = 'tel:'.preg_replace('~^(?:tel\:)+~', '', $link['phone']);
                break;
            case siteMenuLinksConfig::EMAIL_LINK_TYPE:
                $link['email'] = trim($data['email']);
                $link['subject'] = trim($data['subject']);
                $link['url'] = 'mailto:'.preg_replace('~^(?:mailto\:)+~', '', $link['email']);
                if ($link['subject']) {
                    $link['url'] .= '?subject='.rawurlencode($link['subject']);
                }
                break;
            case siteMenuLinksConfig::ANCHOR_LINK_TYPE:
                $link['base_url'] = $data['base_url'];
                $link['url'] = $link['base_url'];
                $link['block_id'] = trim($data['block_id']);
                if ($link['block_id']) {
                    $link['url'] .= '#'.ltrim($link['block_id'], '#');
                }
                break;
        }

        $id = waRequest::get('id', '');
        $this->updateDomainConfig(function(&$domain_config) use ($id, $link) {
            if (strlen($id) && isset($domain_config['apps'][$id])) {
                $domain_config['apps'][$id] = $link;
            } else {
                $id = count($domain_config['apps']);
                $domain_config['apps'][] = $link;
            }

            return [
                'id' => $id,
                'name' => $link['name'],
                'url' => $link['url'],
            ];
        });
    }

    public function deleteAction()
    {
        $id = waRequest::get('id');
        if ($id === null) return;

        $this->updateDomainConfig(function(&$domain_config) use ($id) {
            if (isset($domain_config['apps'][$id])) {
                unset($domain_config['apps'][$id]);
                $domain_config['apps'] = array_values($domain_config['apps']);
            }
        });
    }

    public function moveAction()
    {
        $id = waRequest::post('id');
        if ($id === null) return;

        $before_id = waRequest::post('before_id');
        if ($before_id < 0) $before_id = null;

        if($id === $before_id) return;

        $this->updateDomainConfig(function(&$domain_config) use ($id, $before_id) {
            $sorted_apps = [];
            $moved_app = $domain_config['apps'][$id];
            unset($domain_config['apps'][$id]);

            if ($before_id === null) {
                $sorted_apps[] = $moved_app;
                $before_id = true;
            }
            foreach ($domain_config['apps'] as $i => $app) {
                $i = (string)$i;
                $sorted_apps[] = $app;
                if ($before_id === $i) {
                    $sorted_apps[] = $moved_app;
                    $before_id = true;
                }
            }
            $domain_config['apps'] = $sorted_apps;
        });
    }

    private function prepareData(array $data)
    {
        if (isset($data['rel'])) {
            if (!is_array($data['rel'])) {
                throw new waException('an array of values was expected in the rel[] field.');
            }
            $allowed_rel = array_keys(siteMenuLinksConfig::getLinkRel());
            $data['rel'] = array_filter($data['rel'], function ($val) use ($allowed_rel) {
                return in_array($val, $allowed_rel);
            });
        }

        if (empty($data['type']) || !in_array($data['type'], array_keys(siteMenuLinksConfig::getLinkTypes()))) {
            $data['type'] = siteMenuLinksConfig::CUSTOM_LINK_TYPE;
        }

        return $data;
    }

    private function updateDomainConfig(callable $update)
    {
        list($domain_config, $domain_config_path) = siteMenuLinksConfig::getDomainConfig();

        if (!$domain_config) {
            // create directory
            waFiles::create($domain_config_path);
        }

        $result = $update($domain_config);

        if (!waUtils::varExportToFile($domain_config, $domain_config_path)) {
            $this->displayJson(null,
                sprintf(_w('Settings could not be saved due to insufficient file write permissions for folder “%s”.'), 'wa-config/apps/site/domains')
            );
            return false;
        } else {
            $this->displayJson($result);
        }
    }
}
