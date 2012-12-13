<?php

class guestbook2DesignActions extends waDesignActions
{

    public function __construct()
    {
        // check access rights
        if (!$this->getRights('design')) {
            throw new waRightsException(_ws("Access denied"));
        }
    }


    public function defaultAction()
    {
        $this->setLayout(new guestbook2BackendLayout());
        parent::defaultAction();
    }
}