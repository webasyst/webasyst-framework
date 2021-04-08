<?php
class uiBackendController extends waController
{
    public function execute()
    {
        $last_url = wa()->getUser()->getSettings('ui', 'last_url', 'component/');
        $this->redirect($last_url);
    }
}