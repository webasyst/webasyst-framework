<?php

class sitePluginsActions extends waPluginsActions
{
    public function preExecute()
    {
        if (!$this->getUser()->isAdmin('site')) {
            throw new waRightsException(_ws('Access denied'));
        }
    }
}
