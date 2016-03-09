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
            $sql = "SELECT p.id, p.parent_id, p.name, p.title, p.full_url as url, p.create_datetime, p.update_datetime, pp.value as priority
            FROM ".$page_model->getTableName().' p
            LEFT JOIN '.$page_model->getParamsModel()->getTableName()." pp ON p.id = pp.page_id AND pp.name = 'priority'
            WHERE p.domain_id = i:domain_id AND p.route = s:route AND p.status = 1
            ORDER BY sort";
            $pages = $page_model->query($sql, array('domain_id' => $domain['id'], 'route' => $r['url']))->fetchAll('id');
            // get part of url by route
            $u = $this->getUrlByRoute($r);
            foreach ($pages as $p) {
                if (!empty($p['priority']) && $p['priority'] >= 0 && $p['priority'] <= 100) {
                    $priority = (int)$p['priority']/100.0;
                } else {
                    $priority = false;
                }
                if (!$p['url']) {
                    if ($priority === false) {
                        $priority = 1;
                    }
                    $change = self::CHANGE_WEEKLY;
                } else {
                    if ($priority === false) {
                        $priority = $p['parent_id'] ? 0.2 : 0.6;
                    }
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