<?php

class photosFrontendPageAction extends waPageAction
{

    public function execute()
    {
        $this->setLayout(new photosDefaultFrontendLayout());

        /**
         * @event frontend_sidebar
         * @return array[string][string]string $return[%plugin_id%]['menu'] Sidebar menu item
         * @return array[string][string]string $return[%plugin_id%]['section'] Sidebar section item
         */
        $this->view->assign('frontend_sidebar', wa()->event('frontend_sidebar'));
        parent::execute();
    }
}