<?php

class teamWelcomeAction extends waViewAction
{
    public function execute()
    {
        // Redirect on first login
        if (!wa()->getUser()->isAdmin('webasyst')) {
            $this->redirect(wa()->getConfig()->getBackendUrl(true).wa()->getApp());
        }
        $this->setLayout(new teamDefaultLayout(true));
        if(wa()->whichUI() === '1.3') {
            $this->setTemplate('templates/actions-legacy/Welcome.html');
        }else{
            $this->setTemplate('templates/actions/Welcome.html');
        }
    }
}
