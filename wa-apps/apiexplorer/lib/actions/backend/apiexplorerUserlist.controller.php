<?php

class apiexplorerUserlistController extends apiexplorerJsonController
{
    public function execute()
    {
        if (wa()->appExists('team')) {
            wa('team');
            $hash = wa()->getUser()->isAdmin() ? 'users' : [wa()->getUser()->getId()];
            $users = teamUser::getList($hash, ['order' => 'name', 'fields' => 'minimal']);
            $this->response = ['users' => $users];
        } else {
            $this->response = ['users' => [wa()->getUser()->getId() => wa()->getUser()->load()]];
        }
    }
}
