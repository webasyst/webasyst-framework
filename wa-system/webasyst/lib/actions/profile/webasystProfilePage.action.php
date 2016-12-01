<?php
/**
 * Own profile editor for users who don't have access to Team app.
 */
class webasystProfilePageAction extends waViewAction
{
    public function execute()
    {
        $user = wa()->getUser();
        $user->load();

        /*
         * @event backend_personal_profile
         */
        $params = array(
            'user' => $user,
            'top' => $user->getTopFields(),
        );
        $backend_personal_profile = wa()->event(array('webasyst', 'backend_personal_profile'), $params);

        // Redirect to old Contacts app if user has access to it
        if (wa()->appExists('contacts') && wa()->getUser()->getRights('contacts', 'backend')) {
            wa('contacts', 1)->getResponse()->redirect(wa()->getUrl()."#/contact/{$user['id']}/");
        }

        $this->view->assign('backend_personal_profile', $backend_personal_profile);
        $this->view->assign(array(
            'top' => $params['top'],
            'user' => $user,
        ));
    }
}
