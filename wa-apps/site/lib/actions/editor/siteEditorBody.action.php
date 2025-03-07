<?php
/**
 * Block page editor body (inside iframe)
 */
class siteEditorBodyAction extends waViewAction
{
    public function execute()
    {
        $page_id = waRequest::request('page_id', null, 'int');
        if (!$page_id) {
            throw new waException('page_id is required', 400);
        }
        $blockpage_model = new siteBlockpageModel();
        $page = $blockpage_model->getById($page_id);
        if (!$page) {
            throw new waExeption('Page not found', 404);
        }

        $this->setRoutingDomain($page['domain_id']);

        $page = (new siteBlockPage($page))->getDraftPage();

        $this->setLayout(new siteBlockPageLayout());
        $this->view->assign([
            'page' => $page,
            'rendered_page_html' => $page->renderBackend(),
        ]);
    }

    /** Prepare routing to pretend it's a frontend page view. */
    protected function setRoutingDomain($domain_id)
    {
        $domains = siteHelper::getDomains(true);
        $domain = ifset($domains, $domain_id, null);
        if (!empty($domain['name'])) {
            wa()->getRouting()->setRoute(null, $domain['name']);
        }
        $route = ['url' => '*', 'app' => 'site'];
        foreach(wa()->getRouting()->getRoutes() as $r) {
            if (ifset($r, 'app', null) === 'site') {
                $route = $r;
                if ($r['url'] == '*') {
                    break;
                }
            }
        }
        wa()->getRouting()->setRoute($route);
    }
}
