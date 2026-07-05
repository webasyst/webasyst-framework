<?php

class webasystSettingsAiAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $this->view->assign(webasystHelper::getAiParams());
    }
}
