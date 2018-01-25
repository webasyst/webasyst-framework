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

        $sql = "SELECT id, parent_id, name, title, full_url as url, create_datetime, update_datetime
            FROM ".$page_model->getTableName().'
            WHERE domain_id = i:domain_id AND route = s:route AND status = 1
            ORDER BY sort';
        $pages = $page_model->query($sql, array('domain_id' => $domain['id'], 'route' => $route['url']))->fetchAll('id');

        return $pages;
    }
}