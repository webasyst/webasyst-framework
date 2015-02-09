<?php 

class siteBackendController extends waViewController
{
    public function execute()
    {
        if (($d = waRequest::get('domain_id')) && $d != $this->getUser()->getSettings('site', 'last_domain_id')) {
            $this->getUser()->setSettings('site', 'last_domain_id', $d);
        }
        $this->setLayout(new siteDefaultLayout());
    }
}
