<?php

class blogPagesActions extends waPageActions
{
    protected $ibutton = false;

    public function defaultAction()
    {
        $this->setLayout(new blogDefaultLayout());
        $this->getResponse()->setTitle(_ws('Pages'));

        parent::defaultAction();
    }
}