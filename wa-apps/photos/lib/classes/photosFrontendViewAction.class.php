<?php

class photosFrontendViewAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new photosDefaultFrontendLayout());
        }

        return $this;
    }
}