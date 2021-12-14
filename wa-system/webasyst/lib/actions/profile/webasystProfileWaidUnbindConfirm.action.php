<?php

class webasystProfileWaidUnbindConfirmAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign([
            'id' => $this->getRequest()->get('id'),
            'is_waid_forced' => $this->isWebasystIDForced(),
        ]);
    }

    protected function isWebasystIDForced()
    {
        $cm = new waWebasystIDClientManager();
        return $cm->isBackendAuthForced();
    }
}
