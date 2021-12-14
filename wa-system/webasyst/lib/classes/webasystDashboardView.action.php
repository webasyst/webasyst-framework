<?php

class webasystDashboardViewAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new webasystBackendLayout());
        }
    }

    protected function notFound()
    {
        throw new waException(_w('Page not found'));
    }
}
