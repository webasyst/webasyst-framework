<?php

class teamProfileSidebarDialogAction extends teamContentViewAction {
    public function execute() {
        $this->view->assign([
            'options' => filter_var_array(waRequest::post(), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ]);
        $this->setTemplate('templates/actions/profile/ProfileSidebarDialog.html');
    }
}
