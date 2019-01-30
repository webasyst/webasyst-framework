<?php

class siteBackendController extends waViewController
{
    public function execute()
    {
        $d = waRequest::get('domain_id');
        if ($d && $d != $this->getUser()->getSettings('site', 'last_domain_id')) {
                $this->getUser()->setSettings('site', 'last_domain_id', $d);
        } else {
            //If found problem domain, need redirect user to this domain settlements
            $routing_errors = wa()->getConfig()->getRoutingErrors();
            if (!empty($routing_errors['apps'])) {
                $domain = reset($routing_errors['apps']);
                if (isset($domain['id'])) {
                    $this->getUser()->setSettings('site', 'last_domain_id', $domain['id']);
                }
            }
        }

        $this->setLayout(new siteDefaultLayout());
    }
}
