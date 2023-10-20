<?php

class webasystProfileWaidUnbindConfirmAction extends waViewAction
{
    public function preExecute()
    {
        parent::preExecute();
        $ui = waRequest::get('ui', null, waRequest::TYPE_STRING_TRIM);
        if (in_array($ui, ['1.3', '2.0'])) {
            waRequest::setParam('force_ui_version', $ui);
        }
    }
    
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
