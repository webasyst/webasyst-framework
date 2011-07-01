<?php

class webasystBackendActions extends waViewActions
{

    public function defaultMobileAction()
    {
        $apps = $this->getUser()->getApps();
        $this->view->assign('apps', $apps);
        $backend_url = $this->getConfig()->getBackendUrl(true);
        $this->view->assign('backend_url', $backend_url);
    }

    public function defaultAction()
    {
        $widgets = array();
//		$widgets = array_merge($widgets, waWidgets::load('contacts', array('graph', 'list')));
//		$widgets = array_merge($widgets, waWidgets::load('orders', array('graph', 'orders')));
        $this->view->assign("widgets", $widgets);
        $this->view->assign("username", wa()->getUser()->getName());
    }

    public function logoutAction()
    {
        // Update last datetime of the current user
        waSystem::getInstance()->getUser()->updateLastTime(true);

        // Clear auth data
        waSystem::getInstance()->getAuth()->clearAuth();

        // Redirect to the main page
        $this->redirect($this->getConfig()->getBackendUrl(true));
    }

    /**
     * Userpic
     */
    public function photoAction()
    {
        $id = (int)waRequest::get('id');
        if (!$id) {
            $id = $this->getUser()->getId();
        }

        $contact = new waContact($id);
        $rand = $contact['photo'];
        $file = wa()->getDataPath("photo/$id/$rand.original.jpg", TRUE, 'contacts');

        $size = waRequest::get('size');
        if (!file_exists($file)) {
            $size = (int)$size;
            if (!in_array($size, array(20, 32, 50, 96))) {
                $size = 96;
            }
            waFiles::readFile($this->getConfig()->getRootPath().'/wa-content/img/userpic'.$size.'.jpg');
        } else {
            // original file
            if ($size == 'original') {
                waFiles::readFile($file);
            }
            // cropped file
            elseif ($size == 'full') {
                $file = str_replace('.original.jpg', '.jpg', $file);
                waFiles::readFile($file);
            }
            // thumb
            else {
                if (!$size) {
                    $size = '96x96';
                }
                $size_parts = explode('x', $size, 2);
                $size_parts[0] = (int)$size_parts[0];
                if (!isset($size_parts[1])) {
                    $size_parts[1] = $size_parts[0];
                } else {
                    $size_parts[1] = (int)$size_parts[1];
                }

                if (!$size_parts[0] || !$size_parts[1]) {
                    $size_parts = array(96, 96);
                }

                $size = $size_parts[0].'x'.$size_parts[1];

                $thumb_file = str_replace('.original.jpg', '.'.$size.'.jpg', $file);
                $file = str_replace('.original.jpg', '.jpg', $file);

                if (!file_exists($thumb_file) || filemtime($thumb_file) < filemtime($file)) {
                    waImage::factory($file)->resize($size_parts[0], $size_parts[1])->save($thumb_file);
                    clearstatcache();
                }
                waFiles::readFile($thumb_file);
            }
        }
    }
}

