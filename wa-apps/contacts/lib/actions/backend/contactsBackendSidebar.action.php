<?php

class contactsBackendSidebarAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('views', null);
        $this->view->assign('settings', $this->getUser()->getSettings('contacts'));

        $historyModel = new contactsHistoryModel();
        $this->view->assign('history', $historyModel->get());

        $cc = new contactsCollection();
        $this->view->assign('totalContacts', $cc->count());

        // only show categories available to current user
//        $crm = new contactsRightsModel();
        $wcrm = new waContactRightsModel();
        $ccm = new waContactCategoryModel();
//        $allowed = $crm->getAllowedCategories();
//        $categories = array();
//        if($allowed === true) {
//            $categories = $ccm->getAll();
//        } else if ($allowed) {
//            foreach($ccm->getAll() as $cat) {
//                if (isset($allowed[$cat['id']])) {
//                    $categories[] = $cat;
//                }
//            }
//        }
        
        $categories = $ccm->getAll();
        
        $this->view->assign('categories', $categories);

        // User views are only available to global admin
        $r = new waContactRightsModel();
        $this->view->assign('superadmin', FALSE);
        $this->view->assign('admin', FALSE);
        if (wa()->getUser()->getRights('webasyst', 'backend')) {
            $this->view->assign('superadmin', TRUE);
            $this->view->assign('admin', TRUE);

//            $group_model = new waGroupModel();
//            $this->view->assign('groups', $group_model->getAll());

            $cc = new contactsCollection('/users/all/');
            $this->view->assign('totalUsers', $cc->count());
        } else if (wa()->getUser()->getRights('contacts', 'backend') >= 2) {
            $this->view->assign('admin', TRUE);
        }

        // is user allowed to add contacts?
        $this->view->assign('show_create', $wcrm->get(null, null, 'create'));

        $event_params = array();
        $this->view->assign('backend_sidebar', wa()->event('backend_sidebar', $event_params, array('top_li')));
    }
}

// EOF
