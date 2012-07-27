<?php

class photosImportPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('transports', array(
            'webasyst' => array(
                'name' => _wp('WebAsyst Photos (old version) on the same server'),
                'description' => ''
            ),
            'webasystremote' => array(
                'name' => _wp('WebAsyst Photos (old version) on a remote server'),
                'description' => ''
            ),
            /*
            'folder' => array(
                'name' => 'Folder on the same server',
                'description' => 'Folder on the same server',
            )*/
        ));
    }
}
