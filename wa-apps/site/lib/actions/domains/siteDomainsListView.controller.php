<?php
class siteDomainsListViewController extends waJsonController
{
    public function execute()
    {
        switch (waRequest::post('action')) {
            case 'set':
                wa()->getUser()->setSettings('site', 'list_view', 1);
                break;
            case 'delete':
                wa()->getUser()->delSettings('site', 'list_view');
                break;
        }
    }
}
