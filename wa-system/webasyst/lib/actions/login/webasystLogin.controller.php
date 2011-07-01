<?php

class webasystLoginController extends waViewController
{
    public function execute()
    {
        $contact_model = new waContactModel();
        if ($contact_model->countAll()) {
            $this->executeAction(new webasystLoginAction());
        } else {
            $this->executeAction(new webasystLoginFirstAction());
        }
    }
}

