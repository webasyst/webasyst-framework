<?php

class blogFrontendPageAction extends waPageAction
{

    public function execute()
    {
        $this->setLayout(new blogFrontendLayout());
        parent::execute();
    }
}