<?php

class developerBackendAction extends developerAction
{
    public function execute()
    {
        $error = $this->checkRights();
        if ($error) {
            $this->view->assign('error', $error);
            $this->setTemplate('string:<h2 style="color: red">{$error|escape}</h2>');
        }

        // Browsers don't like it when JS is sent over POST. This disables internal browser's XSS filtering.
        $this->getResponse()
             ->addHeader('X-XSS-Protection', 0)
             ->sendHeaders();
    }

    protected function checkRights()
    {
        // show nice message instead of exception screen
        try {
            parent::checkRights();
        } catch (waException $e) {
            return $e->getMessage();
        }
    }
}
