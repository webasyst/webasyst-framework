<?php

class apiexplorerBackendAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('user_id', wa()->getUser()->getId());
        $this->view->assign('isAdmin', wa()->getUser()->isAdmin());
    }
}
