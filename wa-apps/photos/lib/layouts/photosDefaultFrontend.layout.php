<?php

class photosDefaultFrontendLayout extends waLayout
{
    public function execute()
    {
        $action = waRequest::param('action', 'default');
        $this->view->assign('action', $action);

        $this->view->assign('breadcrumbs', waRequest::param('breadcrumbs', array()));

        if (!$this->getResponse()->getTitle()) {
            $title = waRequest::param('title') ? photosPhoto::escape(waRequest::param('title')) : wa()->accountName();
            $this->getResponse()->setTitle($title);
        }

        $this->view->assign('nofollow', waRequest::param('nofollow', false));

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
        $this->setThemeTemplate('index.html');
    }
}