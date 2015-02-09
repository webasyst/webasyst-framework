<?php
/**
 * User profile form in customer account, and submit controller for it.
 * Controller for my.profile.html in themes.
 */
class blogFrontendMyAction extends waMyProfileAction
{
    public function execute()
    {
        parent::execute();

        $this->view->assign('my_nav_selected', 'profile');
        $user = wa()->getUser();
        $user_info = array();
        foreach($this->form->fields as $id => $field) {
            if (!in_array($id, array('password', 'password_confirm'))) {
                if ($id === 'photo') {
                    $user_info[$id] = array(
                        'name' => _ws('Photo'),
                        'value' => '<img src="'.$user->getPhoto().'">',
                    );
                } else {
                    $user_info[$id] = array(
                        'name' => $this->form->fields[$id]->getName(null, true),
                        'value' => $user->get($id, 'html'),
                    );
                }
            }
        }
        $this->view->assign('user_info', $user_info);

        // Set up layout and template from theme
        $this->setThemeTemplate('my.profile.html');
        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new blogFrontendLayout());
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

