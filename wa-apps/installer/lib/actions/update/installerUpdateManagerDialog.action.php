<?php

class installerUpdateManagerDialogAction extends installerUpdateManagerAction
{
    protected function signalFailMessage($msg)
    {
        $this->view->assign([
            'error_message' => $msg,
        ]);
        echo $this->view->fetch('templates/actions/update/UpdateManagerDialogError.html');
        exit;
    }

    protected function errorNothingToUpdate()
    {
        throw new waException(_w('This product is already installed'));
    }

    protected function ensureLayout()
    {
        // nothing to do
    }
}
