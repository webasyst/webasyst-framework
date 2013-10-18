<?php
class blogDefaultLayout extends waLayout
{
    protected $js = array();

    public function execute()
    {
        $this->executeAction('sidebar', new blogBackendSidebarAction());
        
        /**
         * Include plugins js and css
         * @event backend_assets
         * @return array[string]string $return[%plugin_id%] Extra head tag content
         */
        $this->view->assign('backend_assets', wa()->event('backend_assets'));
        
        $user = $this->getUser();
        $app = $this->getApp();
        $admin = $user->getRights($app,'backend');
        
        $this->assign('rights', array(
                'admin' => $admin,
                'blogs' => $user->getRights($app,'blog.%'),
                'pages' => $user->getRights($app, blogRightConfig::RIGHT_PAGES)
        ));
    }

}