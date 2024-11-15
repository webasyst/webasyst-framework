<?php
/**
 * App main page for UI 2.0. List of sites (domains).
 */
class siteBackendDomainsAction extends waViewAction
{
    public function execute()
    {
        $domains = siteHelper::getDomains(true);

        foreach ($domains as $n => $d) {
            $path = wa()->getDataPath(null, true).'/data/'.$d['name'].'/favicon.ico';
            if (file_exists($path)) {
                $domains[$n]['favicon'] = wa()->getDataUrl('data/'.$d['name'].'/favicon.ico', true);
            } else {
                $path = 'http'.(waRequest::isHttps() ? 's' : '').'://'.$d['name'].'/favicon.ico';
                if (file_exists($path)) {
                    $domains[$n]['favicon'] = $path;
                }
            }
        }


        $this->view->assign([
            'domains' => $domains,
        ]);
    }
}
