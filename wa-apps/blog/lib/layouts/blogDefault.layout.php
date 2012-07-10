<?php
class blogDefaultLayout extends waLayout
{
    protected $js = array();

    public function execute()
    {
        $this->executeAction('sidebar', new blogBackendSidebarAction());
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