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
        $this->setTemplate('templates/actions/Welcome.html');
    }
}
