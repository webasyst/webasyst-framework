<?php 

class siteFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->assign('title', $this->getResponse()->getTitle());
        $this->assign('meta_keywords', $this->getResponse()->getMeta('keywords'));
        $this->assign('meta_description', $this->getResponse()->getMeta('description'));
        $this->setThemeTemplate('index.html');
    }
}