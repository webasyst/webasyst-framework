<?php
/**
 * Plugins tab for a single Site (domain) in UI 2.0
 */
class siteExtensionsActions extends waPluginsActions
{
    protected $is_ajax = false;
    protected $is_no_sidebar_mode = true;

    protected function preExecute() {
        if (wa()->whichUI() === '1.3') {
            throw new waException(_w('Access denied.'));
        }
    }

    public function defaultAction() {
        $this->setLayout(new siteBackendPluginsLayout());

        $this->getView()->assign([
            'domain_id' => siteHelper::getDomainId(),
            'plugin_module' => 'extensions',
        ]);
        parent::defaultAction();
    }

}
