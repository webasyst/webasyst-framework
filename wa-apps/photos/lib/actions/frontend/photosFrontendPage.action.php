<?php

class photosFrontendPageAction extends waPageAction
{

    public function execute()
    {
        $this->setLayout(new photosDefaultFrontendLayout());

        parent::execute();
    }
}