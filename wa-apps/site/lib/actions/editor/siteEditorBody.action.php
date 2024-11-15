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

        $page = (new siteBlockPage($page))->getDraftPage();

        $this->setLayout(new siteBlockPageLayout());
        $this->view->assign([
            'page' => $page,
            'rendered_page_html' => $page->renderBackend(),
        ]);
    }
}
