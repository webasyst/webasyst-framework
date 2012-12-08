<?php

/**
 * Application sidebar. Used as a part of the default layout.
 */
class dictionariesBackendSidebarAction extends waViewAction
{
    public function execute()
    {
        $lm = new dictionariesModel();
        $lists = $lm->getAllowed();

        $id = waRequest::request('id');
        if ( ( $id = waRequest::request('id')) && isset($lists[$id])) {
            $lists[$id]['current'] = true;
        }

        $this->view->assign('lists', $lists);
        $this->view->assign('can_add_lists', $this->getRights('add_list'));
    }
}

