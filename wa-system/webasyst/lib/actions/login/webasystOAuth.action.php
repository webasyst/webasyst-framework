<?php

class webasystOAuthAction extends waViewAction
{
    public function execute()
    {
        $this->template = wa()->getAppPath('templates/actions/oauth/', 'webasyst').'OAuth.html';
    }
}