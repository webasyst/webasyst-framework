<?php

class developerAction extends waViewAction
{
    public function display($clear_assign = true)
    {
        $this->setLayout(new developerBackendLayout());
        return parent::display($clear_assign);
    }
}