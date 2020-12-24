<?php

class webasystDashboardAppsAction extends webasystDashboardViewAction
{
    use webasystHeaderTrait;

    public function execute()
    {
        $this->view->assign([
            'counts' => wa()->getStorage()->read('apps-count'),
            'backend_url' => wa()->getConfig()->getBackendUrl(true),
            'current_app' => wa()->getApp(),
            'request_uri' => waRequest::server('REQUEST_URI'),
            'root_url' => wa()->getRootUrl(),
            'header_items' => $this->getHeaderItems()
        ]);
    }
}
