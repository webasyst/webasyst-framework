<?php

class teamWelcomeAction extends waViewAction
{
    public function execute()
    {
        // Redirect on first login
        if (!wa()->getUser()->isAdmin('webasyst')) {
            $this->redirect(wa()->getConfig()->getBackendUrl(true).wa()->getApp());
        }

        $event_data = [ 'action' => 'welcome' ];
        $event_results = wa('team')->event('welcome', $event_data);

        foreach($event_results as $event_result) {
            if (ifset($event_result['block'], false)) {
                $this->redirect(wa()->getConfig()->getBackendUrl(true).wa()->getApp());
            }
        }
        
        $event_html = '';
        foreach($event_results as $event_result) {
            if (isset($event_result['html'])) {
                $event_html .= $event_result['html'];
            }
        }
        
        $this->view->assign('event_html', $event_html);

        $this->setLayout(new teamDefaultLayout(true));
        if(wa()->whichUI() === '1.3') {
            $this->setTemplate('templates/actions-legacy/Welcome.html');
        }else{
            $this->setTemplate('templates/actions/Welcome.html');
        }
    }
}
