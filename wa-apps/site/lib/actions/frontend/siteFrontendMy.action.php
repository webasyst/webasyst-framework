<?php
/**
 * User profile form in customer account, and submit controller for it.
 * Controller for my.profile.html in themes.
 */
class siteFrontendMyAction extends waMyProfileAction
{
    public function execute()
    {
        parent::execute();

        $this->view->assign('my_nav_selected', 'profile');

        // Set up layout and template from theme
        $this->setThemeTemplate('my.profile.html');
        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new siteFrontendLayout());
            $this->getResponse()->setTitle(_w('My account').' — '._w('My profile'));
            $this->layout->assign('breadcrumbs', $this->getBreadcrumbs());
            $this->layout->assign('nofollow', true);
        }
    }

    public static function getBreadcrumbs()
    {
        return array(
            array(
                'name' => _w('My account'),
                'url' => wa()->getRouteUrl('/frontend/my'),
            ),
        );
    }
}

