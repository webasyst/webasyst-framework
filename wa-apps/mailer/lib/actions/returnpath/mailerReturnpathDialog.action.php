<?php

class mailerReturnpathDialogAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $id = waRequest::request('id');

        $rpm = new mailerReturnPathModel();
        if (!$id) {
            $email = waRequest::request('email');
            $rp = $rpm->getByField('email', $email);
            if ($rp) {
                $id = $rp['id'];
            }
        }

        $return_path = $rpm->getById($id);
        $show_delete_link = $id && $return_path && !mailerHelper::isReturnPathAlive($return_path);

        $this->view->assign('data', $return_path);
        $this->view->assign('show_delete_link', $show_delete_link);
    }
}

