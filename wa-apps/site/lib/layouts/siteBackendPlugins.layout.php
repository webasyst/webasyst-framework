<?php
/**
 * Helper for siteExtensionsActions.
 * Wraps HTML from system content in a container, then wraps everything into a normal siteBackendLayout
 */
class siteBackendPluginsLayout extends waLayout
{
    public function execute()
    {
        $domain_id = waRequest::request('domain_id', null, 'int');
        $domains = siteHelper::getDomains(true);
        if (!$domain_id || empty($domains[$domain_id])) {
            throw new waException('Domain not found', 404);
        }
        $domain = $domains[$domain_id];

        $this->view->assign([
            'domain_id' => $domain_id,
            'domain' => $domain,
        ]);
    }

    public function display()
    {
        $this->execute();
        $template = $this->getTemplate();
        if (!$template || $template === 'string:{$content}') {
            $html = ifset($this->blocks, 'content', '');
        } else {
            $this->view->assign($this->blocks);
            $html = $this->view->fetch($this->getTemplate());
        }

        $outer_layout = new siteBackendLayout();
        $outer_layout->setBlock('content', $html);
        $outer_layout->display();
    }
}
