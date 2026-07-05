<?php

class siteDomainsDuplicateController extends waJsonController
{
    public function execute()
    {
        $source_domain_id = waRequest::request('source_domain_id', null, 'int');
        $dest_domain_id = waRequest::request('dest_domain_id', null, 'int');

        if (!waLicensing::check('site')->hasPremiumLicense()) {
            throw new waException(_w('The premium license is required to copy sites.'), 403);
        }

        $domains = siteHelper::getDomains(true);
        if (!$source_domain_id || empty($domains[$source_domain_id]) || !$dest_domain_id || empty($domains[$dest_domain_id])) {
            throw new waException('source_domain_id and dest_domain_id are required');
        }
        if ($source_domain_id == $dest_domain_id) {
            throw new waException('source_domain_id and dest_domain_id can not be the same');
        }

        $source_domain = $domains[$source_domain_id] + ['id' => $source_domain_id];
        $dest_domain = $domains[$dest_domain_id] + ['id' => $dest_domain_id];
        if ($source_domain['is_alias']) {
            throw new waException('Unable to duplicate an alias');
        }

        $this->deleteSiteContents($dest_domain);
        $this->copySiteContents($source_domain, $dest_domain);

        $this->logAction('site_duplicate', $source_domain['name'].':'.$dest_domain['name']);

        /**
         * @event domain_duplicate
         * @return void
         */
        wa('site')->event('domain_duplicate', ref([
            'source_domain_id' => $source_domain_id,
            'source_domain' => $source_domain,
            'dest_domain_id' => $dest_domain_id,
            'dest_domain' => $dest_domain,
        ]));

        $this->response = [
            'dest_domain_id' => $dest_domain_id,
        ];
    }

    protected function deleteSiteContents($dest_domain)
    {
        // domain config
        $dest_domain_config_path = $this->getConfig()->getConfigPath('domains/' . $dest_domain['name'] . '.php', true, 'site');
        waFiles::delete($dest_domain_config_path);

        // favicon and robots.txt wa-data/public/site/data/<name>
        $dest_domain_files_path = wa()->getDataPath(null, true).'/data/'.$dest_domain['name'];
        if (file_exists($dest_domain_files_path)) {
            waFiles::delete($dest_domain_files_path);
        }

        // HTML pages of all apps
        $routing_path = $this->getConfig()->getPath('config', 'routing');
        if (file_exists($routing_path)) {
            $routes = include($routing_path);
            $domain_routes = ifset($routes, $dest_domain['name'], []);
            foreach ($this->getAllPageModels($domain_routes) as $app_id => $page_model) {
                if ($app_id === 'site') {
                    $domain_field = ['domain_id' => $dest_domain['id']];
                } else {
                    $domain_field = ['domain' => $dest_domain['name']];
                }
                try {
                    $page_model->deleteByField($domain_field);
                } catch (Throwable $e) {
                }
            }
        }

        // block pages
        $blockpage_model = new siteBlockpageModel();
        $ids = array_keys($blockpage_model->select('id')->where('domain_id=?', [$dest_domain['id']])->fetchAll('id'));
        $blockpage_model->delete($ids);
    }

    protected function getAllPageModels($domain_routes)
    {
        $result = [];
        foreach ($domain_routes as $route) {
            if (empty($route['app']) || array_key_exists($route['app'], $result)) {
                continue;
            }
            $result[$route['app']] = siteHelper::getPageModel($route['app']);
        }
        return array_filter($result);
    }

    protected function copySiteContents($source_domain, $dest_domain)
    {
        // Routes
        $routing_path = $this->getConfig()->getPath('config', 'routing');
        if (file_exists($routing_path)) {
            $routes = include($routing_path);
            $routes[$dest_domain['name']] = ifset($routes, $source_domain['name'], []);
            waUtils::varExportToFile($routes, $routing_path);

            // HTML pages of all apps
            foreach ($this->getAllPageModels($routes[$dest_domain['name']]) as $app_id => $page_model) {
                $this->duplicateAllDomainPages($source_domain, $dest_domain, $page_model);
            }
        }

        // Domain config
        $source_domain_config_path = $this->getConfig()->getConfigPath('domains/' . $source_domain['name'] . '.php', true, 'site');
        $dest_domain_config_path = $this->getConfig()->getConfigPath('domains/' . $dest_domain['name'] . '.php', true, 'site');
        if (file_exists($source_domain_config_path)) {
            waFiles::copy($source_domain_config_path, $dest_domain_config_path);
        }

        // Auth and personal account config wa-config/auth.php
        $auth_config_path = $this->getConfig()->getPath('config', 'auth');
        if (file_exists($auth_config_path)) {
            $auth_config = include($auth_config_path);
            if (!empty($auth_config[$source_domain['name']]) || !empty($auth_config[$dest_domain['name']])) {
                $auth_config[$dest_domain['name']] = ifset($auth_config, $source_domain['name'], []);
                waUtils::varExportToFile($auth_config, $auth_config_path);
            }
        }

        // block pages
        $this->duplicateAllDomainPages($source_domain, $dest_domain, new siteBlockpageModel());

        // favicon and robots.txt wa-data/public/site/data/<name>
        $source_domain_files_path = wa()->getDataPath(null, true).'/data/'.$source_domain['name'];
        $dest_domain_files_path = wa()->getDataPath(null, true).'/data/'.$dest_domain['name'];
        if (file_exists($source_domain_files_path)) {
            waFiles::copy($source_domain_files_path, $dest_domain_files_path);
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        if (function_exists('clearstatcache')) {
            @clearstatcache();
        }
    }

    // Create copy of each page found in given $page_model attached to $source_domain, into $dest_domain
    // Works with HTML pages of any app as well as site block pages.
    protected static function duplicateAllDomainPages($source_domain, $dest_domain, $page_model)
    {
        if ($page_model->fieldExists('domain_id')) {
            $query = $page_model->where('domain_id=?', [$source_domain['id']]);
            $update = [
                'domain_id' => $dest_domain['id'],
            ];
        } else {
            $query = $page_model->where('domain=?', [$source_domain['name']]);
            $update = [
                'domain' => $dest_domain['name'],
            ];
        }

        try {
            // fetch page tree to copy
            $pages = $query->select('id,parent_id')->fetchAll('id');
            if (!$pages) {
                return;
            }
        } catch (waException $e) {
            return; // no parent_id field? give up
        }

        // Prepare SQL templates used in the loop below
        $params_sql = null;

        $fields = $page_model->getEmptyRow();
        unset($fields['id']);
        $all_fields = join(',', array_keys($fields));

        $page_sql = sprintf("
            INSERT INTO %s (%s)
            SELECT %s
            FROM %s
            WHERE id=?",
            $page_model->getTableName(),
            $all_fields,
            $all_fields,
            $page_model->getTableName()
        );
        if (!method_exists($page_model, 'copyContents') && method_exists($page_model, 'getParamsModel')) {
            $page_params_model = $page_model->getParamsModel();
            if ($page_params_model) {
                $params_sql = sprintf(
                    "INSERT INTO %s (`page_id`, `name`, `value`)
                     SELECT ?, `name`, `value`
                     FROM %s
                     WHERE page_id=?",
                    $page_params_model->getTableName(),
                    $page_params_model->getTableName()
                );
            }
        }

        // Loop over $unprocessed_ids many times, process one level of descendants each time
        $old_id_to_new_id = [];
        $something_changed = true;
        $unprocessed_ids = array_keys($pages);
        while ($something_changed) {
            $something_changed = false;
            foreach ($unprocessed_ids as $i => $id) {
                $p = $pages[$id];
                if (empty($p['parent_id']) || isset($old_id_to_new_id[$p['parent_id']])) {
                    $new_page_id = null;
                    try {
                        $new_page_id = $page_model->query($page_sql, [$id])->lastInsertId();
                        $page_model->updateById($new_page_id, $update + [
                            'parent_id' => ifset($old_id_to_new_id, $p['parent_id'], null),
                        ]);

                        if (method_exists($page_model, 'copyContents')) {
                            $page_model->copyContents($id, $new_page_id);
                        } else if ($params_sql) {
                            $page_params_model->exec($params_sql, [$new_page_id, $id]);
                        }
                    } catch (waException $e) {
                    }

                    unset($unprocessed_ids[$i]);
                    if ($new_page_id) {
                        $old_id_to_new_id[$id] = $new_page_id;
                        $something_changed = true;
                    }
                }
            }
        }
    }
}
