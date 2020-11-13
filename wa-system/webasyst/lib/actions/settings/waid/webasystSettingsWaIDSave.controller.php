<?php

class webasystSettingsWaIDSaveController extends waJsonController
{
    public function execute()
    {
        $is_backend_auth_forced = $this->getRequest()->post('is_backend_auth_forced');

        // can't turn off standard backend auth if current user is not bound with webasyst ID contact
        if ($is_backend_auth_forced) {
            $webasyst_contact_id = $this->getUser()->getWebasystContactId();
            if (!$webasyst_contact_id) {
                return;
            }
        }

        $cm = new waWebasystIDClientManager();
        $cm->setBackendAuthForced($is_backend_auth_forced);
    }
}
