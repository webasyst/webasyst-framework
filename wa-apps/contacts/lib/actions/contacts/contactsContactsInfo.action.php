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
        
        $cr = new contactsRightsModel();
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
            $r = $cr->getRight(null, $this->id);
            //var_dump($r );exit;
            if (!$r) {
                throw new waRightsException(_w('Access denied'));
            } else {
                $this->view->assign('readonly', $r === 'read');
            }
        }

        $exists = $this->getContactInfo();
        
        if ($exists) {
            $this->getUserInfo();
            
            $this->view->assign('last_view_context', $this->getLastViewContext());

            // collect data from other applications to show in tabs
            if (empty($this->params['limited_own_profile'])) {
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
            $this->view->assign('can_edit', $cr->getRight(null, $this->id));

            // Update history
            if (empty($this->params['limited_own_profile'])) {
                $name = $this->contact->get('name');
                if ($name || $name === '0') {
                    $history = new contactsHistoryModel();
                    $history->save('/contact/'.$this->id, $name);
                }

                // Update history in user's browser
                $historyModel = new contactsHistoryModel();
                $this->view->assign('history', $historyModel->get());
            }

            $this->view->assign('wa_view', $this->view);
            $this->view->assign('access_disable_msg', contactsHelper::getAccessDisableMsg($this->contact));
            $this->view->assign('my_url', wa()->getRootUrl(true).'my/');
            $this->view->assign('backend_url', wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl(false) . '/');
            $this->view->assign('static_url', wa()->getAppStaticUrl('contacts'));
        }
        
        $this->view->assign('exists', $exists);
        
        if ($this->getRequest()->request('standalone')) {
            /**
             * Include plugins js and css
             * @event backend_assets
             * @return array[string]string $return[%plugin_id%]
             */
            $this->view->assign('backend_assets', wa()->event('backend_assets'));
        }
        
        $auth = wa()->getAuthConfig();
        $this->view->assign('personal_portal_available', !empty($auth['app']));
        
        /*
         * @event backend_contact_info
         * @return array[string]array $return[%plugin_id%] array of html output
         * @return array[string][string]string $return[%plugin_id%]['after_header'] html output
         * @return array[string][string]string $return[%plugin_id%]['header'] html output
         * @return array[string][string]string $return[%plugin_id%]['before_header'] html output
         * @return array[string][string]string $return[%plugin_id%]['before_top'] html output
         * @return array[string][string]string $return[%plugin_id%]['top'] html output
         * @return array[string][string]string $return[%plugin_id%]['after_top'] html output
         * @return array[string][string]string $return[%plugin_id%]['photo'] html output
         */
        $backend_contact_info_params = array(
            'contact_id' => $this->id
        );
        $this->view->assign('backend_contact_info', wa()->event('backend_contact_info', $backend_contact_info_params));
        
    }

    /** Using $this->id get waContact and save it in $this->contact;
      * Load vars into $this->view specific to waContact. */
    protected function getContactInfo()
    {
        $system = wa();
        if ($this->id == $system->getUser()->getId()) {
            $this->contact = $system->getUser();
            $this->view->assign('own_profile', true);
        } else {
            $this->contact = new waContact($this->id);
        }
        
        $exists = $this->contact->exists();
        
        if ($exists) {
            
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

            $this->view->assign('top', contactsHelper::getTop($this->contact));

            // Main contact editor data
            $fieldValues = $this->contact->load('js', true);
            $m = new waContactModel();
            if (isset($fieldValues['company_contact_id'])) {
                if (!$m->getById($fieldValues['company_contact_id'])) {
                    $fieldValues['company_contact_id'] = 0;
                    $this->contact->save(array('company_contact_id' => 0));
                }
            }

            $contactFields = waContactFields::getInfo($this->contact['is_company'] ? 'company' : 'person', true);

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

            contactsHelper::normalzieContactFieldValues($fieldValues, $contactFields);
            $this->view->assign('contactFields', $contactFields);
            $this->view->assign('contactFieldsOrder', array_keys($contactFields));
            $this->view->assign('fieldValues', $fieldValues);

            // Contact categories
            $cm = new waContactCategoriesModel();
            $this->view->assign('contact_categories', array_values($cm->getContactCategories($this->id)));        
            
        } else {
            $this->view->assign('contact', array('id' => $this->id));
        }
        
        return $exists;
        
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
        $noAccess = true;
        $gNoAccess = true;
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
        $this->view->assign('access_to_contacts', $this->getUser()->getRights('contacts', 'backend'));
    }
    
    public function getLastViewContext()
    {
        if ($this->getRequest()->get('last_hash') === null) {
            return null;
        }
        
        $params = array(
            'hash' => $this->getRequest()->get('last_hash', ''),
            'sort' => $this->getRequest()->get('sort', ''),
            'order' => $this->getRequest()->get('order', 1, 'int') ? ' ASC' : ' DESC',
            'offset' => $this->getRequest()->get('offset', 0)
        );
        
        $context = null;
        $plugins_context = wa()->event('backend_last_view_context', $params);
        foreach ($plugins_context as $cntx) {
            if (!empty($cntx)) {
                $context = $cntx;
                break;
            }
        }
        
        if (!$context) {
            
            $hash = $params['hash'];
            $sort = $params['sort'];
            $order = $params['order'];
            $offset = $params['offset'];
            
            $collection = new contactsCollection($hash);
            if ($sort) {
                $collection->orderBy($sort, $order);
            }
            
            $total_count = $collection->count();        
            $ids = array_keys($collection->getContacts('id', max($offset - 1, 0), 3));

            $prev = null;
            $next = null;

            if ($offset > 0) {
                $prev = $ids[0];
                if ($offset < $total_count - 1) {
                    $next = $ids[2];
                }
            } else {
                if ($offset < $total_count - 1) {
                    $next = $ids[1];
                }
            }
            $context = array(
                'total_count' => $total_count,
                'offset' => $offset,
                'prev' => $prev,
                'next' => $next
            );
        }
        return $context;
    }
    
}

// EOF
