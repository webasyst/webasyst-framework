<?php

class teamProfileWaidAccountInfoAction extends waViewAction
{
    public function execute()
    {
        if (!$this->hasAccess()) {
            $this->view->assign([
                'error' => _w('Access denied'),
                'info' => []
            ]);
            return;
        }

        $error = null;
        $info = $this->getInfo();
        if (!$info) {
            $error = _w("Cannot load Webasyst ID account data.");
        }

        $this->view->assign([
            'error' => $error,
            'info' => $info
        ]);
    }

    /**
     * @return bool
     * @throws waException
     */
    protected function hasAccess()
    {
        $contact_id = $this->getContactId();
        return wa()->getUser()->isAdmin('webasyst') || $contact_id === intval($contact_id);
    }

    public function getInfo()
    {
        $contact_id = $this->getContactId();
        if (!$contact_id) {
            return null;
        }
        $api = new waWebasystIDApi();
        return $api->getProfileInfo($contact_id);
    }

    protected function getContactId()
    {
        return wa()->getRequest()->get('id', 0, waRequest::TYPE_INT);
    }


}
