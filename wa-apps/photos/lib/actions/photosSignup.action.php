<?php

class photosSignupAction extends waSignupAction
{
    public function execute()
    {
        $this->setLayout(new photosDefaultFrontendLayout());
        $this->setThemeTemplate('signup.html');
        try {
            parent::execute();
        } catch (waException $e) {
            if ($e->getCode() == 404) {
                $this->view->assign('error_code', $e->getCode());
                $this->view->assign('error_message', $e->getMessage());
                $this->setThemeTemplate('error.html');
            } else {
                throw $e;
            }
        }
    }

    public function afterSignup(waContact $contact)
    {
        $contact->addToCategory($this->getAppId());
    }
}