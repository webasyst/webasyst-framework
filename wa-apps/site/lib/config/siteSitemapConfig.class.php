<?php

class siteSitemapConfig extends waSitemapConfig
{
    public function execute()
    {
        // get all routes of the app site
        $routes = $this->getRoutes();
        $page_model = new sitePageModel();
        foreach ($routes as $route) {
            $this->addPages($page_model,$route);
        }

        // get blockpages
        $this->addPages(new siteBlockpageModel(), null);
    }

    /**
     * @param $page_model
     * @param $route
     * @return array|null|void
     */
    protected function getPages($page_model, $route)
    {
        $domain_model = new siteDomainModel();
        $domain = $domain_model->getByName($this->domain);

        if (!$domain) {
            return;
        }

        if ($page_model instanceof siteBlockpageModel) {
            $sql = "SELECT id, parent_id, full_url as url, create_datetime, update_datetime
                FROM ".$page_model->getTableName().'
                WHERE domain_id = i:domain_id AND status = "final_published"
                ORDER BY sort';
            $pages = array_map(function($p) {
                $p['url'] = rtrim($p['url'], '/').'/';
                return $p;
            }, $page_model->query($sql, ['domain_id' => $domain['id']])->fetchAll('id'));
        } else {
            $sql = "SELECT id, parent_id, name, title, full_url as url, create_datetime, update_datetime
                FROM ".$page_model->getTableName().'
                WHERE domain_id = i:domain_id AND route = s:route AND status = 1
                ORDER BY sort';
            $pages = $page_model->query($sql, array('domain_id' => $domain['id'], 'route' => $route['url']))->fetchAll('id');
        }

        return $pages;
    }

    public function getUrlByRoute($route)
    {
        return parent::getUrlByRoute(ifset($route, [
            'url' => '*',
        ]));
    }
}
