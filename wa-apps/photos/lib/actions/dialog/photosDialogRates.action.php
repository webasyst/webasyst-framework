<?php

class photosDialogRatesAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('max_rate', 5);
    }
}