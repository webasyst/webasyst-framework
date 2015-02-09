<?php 

class siteFrontendController extends waViewController
{
    public function execute()
    {
         $cache = null;
        if ($cache_time = $this->getConfig()->getOption('cache_time')) {
            //$cache = new waSerializeCache('pages/'.$domain.$url.'page');
        }
        $page = array();
        if ($cache && $cache->isCached()) {
            $page = $cache->get();
        } else {
            $site = new siteFrontend();
            if (waRequest::param('error')) {
                $page = array();
            } else {
                $page = $site->getPage(waRequest::param('url', ''));
            }
            if ($page && $cache) {
                $cache->set($page);
            }
        }

        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new siteFrontendLayout());
        }

        try {
            $this->executeAction(new siteFrontendAction($page));
        } catch (Exception $e) {
            if (waSystemConfig::isDebug()) { 
                echo $e;
            } else {
                waSystem::setActive('site');
                $this->executeAction(new siteFrontendAction($e));
            }
        }
    }
}