<?php

class photosImportPluginSettingsTransportAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::request('id');
        $class = 'photosImport'.ucfirst($id).'Transport';
        if ($id && class_exists($class)) {
            /**
             * @var photosImportTransport $transport
             */
            $transport = new $class();
        } else {
            throw new waException('Transport not found', 404);
        }

        $this->view->assign('controls', $transport->getControls());
        $this->view->assign('contacts', waUser::getUsers('photos'));
        $this->view->assign('user_id', $this->getUser()->getId());
    }
}