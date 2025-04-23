<?php

abstract class developerAction extends waViewAction
{
    public function display($clear_assign = true)
    {
        if (!$this->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException(_w('Coding sandbox is available for Webasyst admin users only.'));
        }

        $this->setLayout(new developerBackendLayout());
        return parent::display($clear_assign);
    }
}
