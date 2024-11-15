<?php
/**
 * Blockpage settings (name, url, SEO etc.)
 * Used as a separate controller as well as a part of main Editor screen,
 * @see siteEditorAction
 */
class siteMapPageSettingsDialogAction extends waViewAction
{
    public $page_id;

    public function __construct($params = null)
    {
        parent::__construct($params);
        if (isset($params['page_id'])) {
            $this->page_id = $params['page_id'];
        } else {
            $this->page_id = waRequest::request('page_id', null, 'int');
        }
        if (!$this->page_id) {
            throw new waException('page_id is required', 400);
        }
    }

    public function execute()
    {
        $blockpage_model = new siteBlockpageModel();
        $page = $blockpage_model->getById($this->page_id);
        if (!$page) {
            throw new waException('Page not found', 404);
        }

        $idna = new waIdna();
        $domain_decoded = $idna->decode(siteHelper::getDomain());

        $this->view->assign([
            'page' => $page,
            'domain_id' => siteHelper::getDomainId(),
            'domain_decoded' => $domain_decoded,
            // TODO: add $is_main_page
        ]);
    }
}
