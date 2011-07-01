<?php

/** Contact profile view and editor form. */
class contactsContactsInfoAction extends waViewAction
{
    /**
     * @var waContact|waUser
     */
    protected $contact;

    public function execute()
    {
        $system = wa();
        $datetime = $system->getDateTime();
        if (! ( $this->id = (int)waRequest::get('id'))) {
            throw new waException('No id specified.');
        }

        $user = $this->getUser()->getRights('contacts', 'backend');
        $admin = $user >= 2;
        $ownProfile = $this->id == wa()->getUser()->getId();

        $cr = new contactsRightsModel();
        if (!$cr->getRight(null, $this->id)) {
            if ($user && $ownProfile) {
                $this->view->assign('readonly', true);
            } else {
                throw new waRightsException('Access denied.');
            }
        }

        $this->getContactInfo();
        $this->getUserInfo();

        // free or premium app?
        $this->view->assign('versionFull', waRequest::param('full'));

        // collect data from other applications to show in tabs (for premium app only)
        if (waRequest::param('full')) {
            $links = wa()->event('info', $this->id);
            $this->view->assign('links', $links);
        }

        // tab to open by default
        $this->view->assign('tab', waRequest::get('tab'));

        $this->view->assign('admin', $admin);
        $this->view->assign('superadmin', $admin && $this->getUser()->getRights('webasyst', 'backend'));
        $this->view->assign('current_user_id', wa()->getUser()->getId());
        $this->view->assign('limitedCategories', $admin || $this->getRights('category.all') ? 0 : 1);

        // Update history
        if( ( $name = $this->contact->get('name')) || $name === '0') {
            $name = trim($this->contact->get('title').' '.$name);
            $history = new contactsHistoryModel();
            $history->save('/contact/'.$this->id, $name);
        }

        // Update history in user's browser
        $historyModel = new contactsHistoryModel();
        $this->view->assign('history', $historyModel->get());
        
        $this->view->assign('wa_view', $this->view);
    }

    /** Using $this->id get waContact and save it in $this->contact;
      * Load vars into $this->view specific to waContact. */
    protected function getContactInfo()
    {
        $system = wa();
        if ($this->id == $system->getUser()->getId()) {
            $this->contact = $system->getUser();
            $this->view->assign('own_profile', TRUE);
        } else {
            $this->contact = new waContact($this->id);
        }

        //
        // Load vars into view
        //
        $this->view->assign('contact', $this->contact);

        // who created this contact and when
        $this->view->assign('contact_create_time', waDateTime::format('datetime', $this->contact['create_datetime'], $system->getUser()->getTimezone()));
        if ($this->contact['create_contact_id']) {
            $author = new waContact($this->contact['create_contact_id']);
            if ($author['name']) {
                $this->view->assign('author', $author);
            }
        }

        // Info above tabs
        $fields = array('email', 'phone', 'im');
        $top = array();
        foreach ($fields as $f) {
            if ( ( $v = $this->contact->get($f, 'top,html'))) {
                $top[] = array(
                    'id' => $f,
                    'name' => waContactFields::get($f)->getName(),
                    'value' => is_array($v) ? implode(', ', $v) : $v,
                );
            }
        }
        $this->view->assign('top', $top);

        // Main contact editor data
        $fieldValues = $this->contact->load('js', TRUE);
        $contactFields = waContactFields::getInfo($this->contact['is_company'] ? 'company' : 'person', TRUE);
        $this->view->assign('contactFields', $contactFields);
        $this->view->assign('fieldValues', $fieldValues);

        // Contact categories
        $cm = new waContactCategoriesModel();
        $this->view->assign('contact_categories', array_values($cm->getContactCategories($this->id)));
    }

    /** Using $this->id and $this->contact, if contact is a user,
      * collect and load vars into $this->view specific to waUser. */
    protected function getUserInfo()
    {
        $system = waSystem::getInstance();
        $rm = new waContactRightsModel();
        $ugm = new waUserGroupsModel();
        $gm = new waGroupModel();

        // Personal and group access rights
        $groups = $ugm->getGroups($this->id);
        $ownAccess = $rm->getApps(-$this->id, 'backend', FALSE, FALSE);
        $groupAccess = $rm->getApps(array_keys($groups), 'backend', FALSE, FALSE);
        if(!isset($ownAccess['webasyst'])) {
            $ownAccess['webasyst'] = 0;
        }
        if(!isset($groupAccess['webasyst'])) {
            $groupAccess['webasyst'] = 0;
        }

        // Build application list with personal and group access rights for each app
        $apps = $system->getApps();
        $noAccess = TRUE;
        $gNoAccess = TRUE;
        foreach($apps as $app_id => &$app) {
            $app['id'] = $app_id;
            $app['customizable'] = isset($app['rights']) ? (boolean) $app['rights'] : false;
            $app['access'] = $ownAccess['webasyst'] ? 2 : 0;
            if (!$app['access'] && isset($ownAccess[$app_id])) {
                $app['access'] = $ownAccess[$app_id];
            }
            $app['gaccess'] = $groupAccess['webasyst'] ? 2 : 0;
            if (!$app['gaccess'] && isset($groupAccess[$app_id])) {
                $app['gaccess'] = $groupAccess[$app_id];
            }
            $noAccess = $noAccess && !$app['gaccess'] && !$app['access'];
            $gNoAccess = $gNoAccess && !$app['gaccess'];
        }
        unset($app);

        $this->view->assign('apps', $apps);
        $this->view->assign('groups', $groups);
        $this->view->assign('noAccess', $noAccess ? 1 : 0);
        $this->view->assign('gNoAccess', $gNoAccess ? 1 : 0);
        $this->view->assign('all_groups', $gm->getNames());
        $this->view->assign('fullAccess', $ownAccess['webasyst']);
        $this->view->assign('gFullAccess', $groupAccess['webasyst']);
    }
}

// EOF
