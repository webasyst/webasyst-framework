<?php
/**
 * App main page for UI 1.3 and 2.0.
 * UI 1.3 does not require a separate action, all heavy lifting is done by siteDefaultLayout.
 * UI 2.0 delegates to siteBackendDomainsAction.
 */
class siteBackendController extends waViewController
{
    public function execute()
    {
        if (wa()->whichUI() == '1.3') {
            $this->execute13();
        } else {
            $this->execute20();
        }
    }

    public function execute13()
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

    public function execute20()
    {
        if (null === waRequest::get('list')) {
            $last_domain_id = $this->getUser()->getSettings('site', 'last_domain_id');
            if ($last_domain_id) {

                if (isset(siteHelper::getDomains()[$last_domain_id])) {
                    $this->redirect('?module=map&action=overview&domain_id='.$last_domain_id);
                }
            }
        }
        $this->executeAction(new siteBackendDomainsAction());
        $this->setLayout(new siteBackendLayout());
    }
}
