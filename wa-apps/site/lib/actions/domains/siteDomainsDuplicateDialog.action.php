<?php
/**
 * HTML for dialog to duplicate site contents into another (possibly new) site.
 * Premium only.
 */
class siteDomainsDuplicateDialogAction extends waViewAction
{
    public function execute()
    {
        $domain_id = waRequest::request('domain_id', null, 'int');

        $domains = siteHelper::getDomains(true);
        $domains = array_filter($domains, function($d) {
            return !$d['is_alias'];
        });

        if (!$domain_id || empty($domains[$domain_id])) {
            throw new waException('Source domain_id is required.');
        }
        $domain = $domains[$domain_id] + ['id' => $domain_id];
        unset($domains[$domain_id]);

        $this->view->assign([
            'domain_id' => $domain_id,
            'domains' => $domains,
            'domain' => $domain,
        ]);
    }
}
