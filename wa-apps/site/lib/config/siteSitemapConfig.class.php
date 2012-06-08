<?php

class siteSitemapConfig extends waSitemapConfig
{
    public function execute()
    {
        $domain_model = new siteDomainModel();
        $domain = $domain_model->getByName($this->domain);
        
        if (!$domain) {
            return;
        }
        // get all routes of the app site
        $routes = $this->getRoutes();
        $page_model = new sitePageModel();
        foreach ($routes as $r) {
            $exclude_ids = isset($r['_exclude']) ? $r['_exclude'] : array();
            $sql = "SELECT id, name, title, url, create_datetime, update_datetime FROM ".$page_model->getTableName().'
                WHERE domain_id = i:domain_id AND status = 1'.
                ($exclude_ids ? " AND id NOT IN (:ids)" : '').
                ' ORDER BY sort';
            $pages = $page_model->query($sql, array('domain_id' => $domain['id'], 'ids' => $exclude_ids))->fetchAll('id');
            // get part of url by route
            $u = $this->getUrlByRoute($r);
            foreach ($pages as $p) {
                if (!$p['url']) {
                    $priority = 1;
                    $change = self::CHANGE_WEEKLY;
                } else {
                    $priority = 0.2;
                    $change = self::CHANGE_MONTHLY;
                }
                $p['url'] = $u.$p['url'];
                if (strpos($p['url'], '<') === false) {
                    $this->addUrl($p['url'], $p['update_datetime'], $change, $priority);
                }
            }
        }
    }
}