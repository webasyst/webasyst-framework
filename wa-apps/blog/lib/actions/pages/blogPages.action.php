<?php

class blogPagesAction extends waPageEditAction
{
    protected $ibutton = false;

    public function execute()
    {
        $this->setLayout(new blogDefaultLayout());
        $this->getResponse()->setTitle(_ws('Pages'));

        parent::execute();
    }
}