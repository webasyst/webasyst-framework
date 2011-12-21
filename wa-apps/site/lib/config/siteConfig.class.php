<?php 

class siteConfig extends waAppConfig
{
    public function checkRights($module, $action)
    {
        switch ($module) {
            case 'files':
                if ($action == 'uploadimage') {
                    return true;
                }
                return wa()->getUser()->isAdmin($this->application);
            case 'domains':
            case 'themes':
            case 'snippets':
                return wa()->getUser()->isAdmin($this->application); 
        }
        return true;
    }
}