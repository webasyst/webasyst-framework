<?php

/**
 * Contact profile view and editor form.
 *
 * This action is also used in own profile editor, even when user has no access to Contacts app.
 * See 'profile' module in 'webasyst' system app.
 */
class contactsContactsInfoAction extends waViewAction
{
    /**
     * @var waContact|waUser
     */
    protected $contact;
    protected $id;

    public function execute()
    {
        $system = wa();
        $datetime = $system->getDateTime();
        $user = $this->getUser()->getRights('contacts', 'backend');
        $admin = $user >= 2;

        if (!empty($this->params['limited_own_profile'])) {
            $this->id = wa()->getUser()->getId();
            $this->view->assign('limited_own_profile', true);
            $this->view->assign('save_url', '?module=profile&action=save');
            $this->view->assign('password_save_url', '?module=profile&action=password');
            $this->view->assign('photo_upload_url', '?module=profile&action=tmpimage');
            $this->view->assign('photo_editor_url', '?module=profile&action=photo');
            $this->view->assign('photo_editor_uploaded_url', '?module=profile&action=photo&uploaded=1');
        } else {
            $this->id = (int) waRequest::get('id');
            if (empty($this->id)) {
                throw new waException('No id specified.');
            }
            $cr = new contactsRightsModel();
            if (!$cr->getRight(null, $this->id)) {
                if ($user && $this->id == wa()->getUser()->getId()) {
                    $this->view->assign('readonly', true);
                } else {
                    throw new waRightsException('Access denied.');
                }
            }
        }

        $this->getContactInfo();
        $this->getUserInfo();

        // free or premium app?
        $this->view->assign('versionFull', $this->getConfig()->getInfo('edition') === 'full');

        // collect data from other applications to show in tabs (for premium app only)
        if ($this->getConfig()->getInfo('edition') === 'full' && empty($this->params['limited_own_profile'])) {
            $links = array();
            foreach(wa()->event('profile.tab', $this->id) as $app_id => $one_or_more_links) {
                if (!isset($one_or_more_links['html'])) {
                    $i = '';
                    foreach($one_or_more_links as $link) {
                        $key = isset($link['id']) ? $link['id'] : $app_id.$i;
                        $links[$key] = $link;
                        $i++;
                    }
                } else {
                    $key = isset($one_or_more_links['id']) ? $one_or_more_links['id'] : $app_id;
                    $links[$key] = $one_or_more_links;
                }
            }
            $this->view->assign('links', $links);
        }

        // tab to open by default
        $this->view->assign('tab', waRequest::get('tab'));

        $this->view->assign('admin', $admin);
        $this->view->assign('superadmin', $admin && $this->getUser()->getRights('webasyst', 'backend'));
        $this->view->assign('current_user_id', wa()->getUser()->getId());
        $this->view->assign('limitedCategories', $admin || $this->getRights('category.all') ? 0 : 1);

        // Update history
        if (empty($this->params['limited_own_profile'])) {
            if( ( $name = $this->contact->get('name')) || $name === '0') {
                $name = trim($this->contact->get('title').' '.$name);
                $history = new contactsHistoryModel();
                $history->save('/contact/'.$this->id, $name);
            }

            // Update history in user's browser
            $historyModel = new contactsHistoryModel();
            $this->view->assign('history', $historyModel->get());
        }

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
            try {
                $author = new waContact($this->contact['create_contact_id']);
                if ($author['name']) {
                    $this->view->assign('author', $author);
                }
            } catch (Exception $e) {
                // Contact not found. Ignore silently.
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

        // Only show fields that are allowed in own profile
        if (!empty($this->params['limited_own_profile'])) {
            $allowed = array();
            foreach(waContactFields::getAll('person') as $f) {
                if ($f->getParameter('allow_self_edit')) {
                    $allowed[$f->getId()] = true;
                }
            }

            $fieldValues = array_intersect_key($fieldValues, $allowed);
            $contactFields = array_intersect_key($contactFields, $allowed);
        }

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
