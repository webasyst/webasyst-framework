<?php

/**
 * Application sidebar. Used as a part of the default layout.
 */
class checklistsBackendSidebarAction extends waViewAction
{
    public function execute()
    {
        $lm = new checklistsListModel();
        $lists = $lm->getAllowed();
        foreach($lists as $id => &$list) {
            if (strtolower(substr($list['icon'], 0, 7)) == 'http://') {
                $list['icon'] = '<i class="icon16" style="background-image:url('.htmlspecialchars($list['icon']).')"></i>';
            } else {
                $list['icon'] = '<i class="icon16 '.$list['icon'].'"></i>';
            }
        }

        $id = waRequest::request('id');
        if ( ( $id = waRequest::request('id')) && isset($lists[$id])) {
            $lists[$id]['current'] = true;
        }

        $this->view->assign('lists', $lists);
        $this->view->assign('can_add_lists', $this->getRights('add_list'));
    }
}

