<?php

class installerUpdateManagerDialogAction extends installerUpdateManagerAction
{
    protected function signalFailMessage($msg, $msg_code = null)
    {
        $this->view->assign([
            'error_message' => $msg,
            'error_message_code' => $msg_code,
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
