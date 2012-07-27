<?php

class photosDialogConfirmViewAction extends waViewAction
{
    protected $type = '';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->template = 'templates/actions/dialog/DialogConfirm.html';
    }

    public function display($clear_assign = true) {
        $this->view->assign('type', $this->type);
        $this->view->assign('templates_path', 'templates/actions/dialog/');
        return parent::display($clear_assign);
    }
}