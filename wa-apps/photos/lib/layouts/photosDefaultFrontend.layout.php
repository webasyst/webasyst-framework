<?php

class photosDefaultFrontendLayout extends waLayout
{
    public function execute()
    {
        $action = waRequest::param('action', 'default');
        $disable_sidebar = waRequest::param('disable_sidebar', false);
        $this->view->assign('action', $action);

        $this->view->assign('breadcrumbs', waRequest::param('breadcrumbs', array()));

        if (!$this->getResponse()->getTitle()) {
            $title = waRequest::param('title') ? waRequest::param('title') : wa()->accountName();
            $this->getResponse()->setTitle($title);
        }

        $this->view->assign('nofollow', waRequest::param('nofollow', false));
        $this->view->assign('disable_sidebar', $disable_sidebar);

        /**
         * Include plugins js and css
         * @event frontend_assets
         * @return array[string][string]string $return[%plugin_id%] Extra header data (css/js/meta)
         */
        $this->view->assign('frontend_assets', wa()->event('frontend_assets'));

        /**
         * @event frontend_layout
         * @return array[string][string]string $return[%plugin_id%]['header'] Header menu section
         * @return array[string][string]string $return[%plugin_id%]['footer'] Footer section
         */
        $this->view->assign('frontend_layout', wa()->event('frontend_layout'));


        /**
         * @event frontend_sidebar
         * @return array[string][string]string $return[%plugin_id%]['menu'] Sidebar menu item
         * @return array[string][string]string $return[%plugin_id%]['section'] Sidebar section item
         */
        $this->view->assign('frontend_sidebar', wa()->event('frontend_sidebar'));

        $this->setThemeTemplate('index.html');
    }
}