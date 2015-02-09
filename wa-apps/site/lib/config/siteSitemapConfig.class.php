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
            $sql = "SELECT id, parent_id, name, title, full_url as url, create_datetime, update_datetime
            FROM ".$page_model->getTableName().'
            WHERE domain_id = i:domain_id AND route = s:route AND status = 1
            ORDER BY sort';
            $pages = $page_model->query($sql, array('domain_id' => $domain['id'], 'route' => $r['url']))->fetchAll('id');
            // get part of url by route
            $u = $this->getUrlByRoute($r);
            foreach ($pages as $p) {
                if (!$p['url']) {
                    $priority = 1;
                    $change = self::CHANGE_WEEKLY;
                } else {
                    $priority = $p['parent_id'] ? 0.2 : 0.6;
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