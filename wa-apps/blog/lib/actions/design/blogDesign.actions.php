<?php

class blogDesignActions extends waDesignActions
{
    public function __construct()
    {
        if (!$this->getRights('design')) {
            throw new waRightsException(_ws("Access denied"));
        }
        $this->options['js']['storage'] = false;
    }

    public function defaultAction()
    {
        $this->setLayout(new blogDefaultLayout());
        $this->getResponse()->setTitle(_ws('Design'));

        parent::defaultAction();
    }
}
