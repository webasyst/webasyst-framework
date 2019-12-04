<?php 

class siteFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->assign('title', $this->getResponse()->getTitle());
        $this->assign('meta_keywords', $this->getResponse()->getMeta('keywords'));
        $this->assign('meta_description', $this->getResponse()->getMeta('description'));
        $this->setThemeTemplate('index.html');
        
        /**
         * @event frontend_head
         * @return array[string]string $return[%plugin_id%] html output
         */
        $this->view->assign('frontend_head', wa()->event('frontend_head'));

        /**
         * @event frontend_header
         * @return array[string]string $return[%plugin_id%] html output
         */
        $this->view->assign('frontend_header', wa()->event('frontend_header'));

        if (!$this->view->getVars('frontend_nav')) {
            /**
             * @event frontend_nav
             * @return array[string]string $return[%plugin_id%] html output for navigation section
             */
            $this->view->assign('frontend_nav', wa()->event('frontend_nav'));

            /**
             * @event frontend_nav_aux
             * @return array[string]string $return[%plugin_id%] html output for navigation section
             */
            $this->view->assign('frontend_nav_aux', wa()->event('frontend_nav_aux'));
        }
    }
}
