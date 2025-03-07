<?php
class siteMapSetMainPageController extends waJsonController
{
    public function execute()
    {
        $domain_id = waRequest::request('domain_id', null, waRequest::TYPE_INT);
        $type = waRequest::post('type');
        if (!$domain_id || !$type) {
            $this->setError("Bad Request.", 400);
            return;
        }
        $page_id = waRequest::post('page_id');
        $route_id = waRequest::post('route_id');
        if ($route_id === null && !$page_id) {
            $this->setError("Set not found.", 404);
            return;
        }

        $main_page = new siteMainPage($domain_id);

        $main_page->silenceMainPage();

        $id = $page_id;
        if ($type === 'route_app') {
            $id = $route_id;
        }
        $app_id = waRequest::request('app_id', 'site');
        $main_page->setNewMainPage($app_id, $type, $id);

        $main_page->saveRoutes();
    }

}
