<?php

class blogDesignActions extends waDesignActions
{
    public function __construct()
    {
        if (!$this->getRights('design')) {
            throw new waRightsException("Access denued");
        }
        $this->design_url = '?module=design#/';
        $this->themes_url = '?module=design#/themes';
    }

    public function defaultAction()
    {
        $this->setLayout(new blogDefaultLayout());
        $this->getResponse()->setTitle(_ws('Design'));

        parent::defaultAction();
    }
}
