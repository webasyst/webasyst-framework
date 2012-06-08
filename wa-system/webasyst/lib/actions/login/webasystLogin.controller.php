<?php

class webasystLoginController extends waViewController
{
    public function execute()
    {
        try {
            $contact_model = new waContactModel();
            if ($contact_model->countAll()) {
                $this->executeAction(new webasystLoginAction());
            } else {
                $this->executeAction(new webasystLoginFirstAction());
            }
        } catch (waException $e) {
            if ($e->getCode() == 600) {
                $this->executeAction(new webasystLoginConfigAction());
            }
        }
    }
}

