<?php

class photosDialogConfirmUnstackAction extends photosDialogConfirmViewAction
{
    public function __construct($params=null) {
        parent::__construct($params);
        $this->type = 'unstack';
    }

    public function execute()
    {
        $photos_count = waRequest::get('cnt', 0, waRequest::TYPE_INT);
        if (!$photos_count) {
            throw new waException(_w('Stack is empty'));
        }
        $this->view->assign('photos_count', $photos_count);
    }
}