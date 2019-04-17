<?php

class photosLoginAction extends waLoginAction
{

    public function execute()
    {
        $this->setLayout(new photosDefaultFrontendLayout());
        $this->setThemeTemplate('login.html');
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

}
