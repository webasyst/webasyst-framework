<?php

class webasystBackendActions extends waViewActions
{
    use webasystHeaderTrait;

    public function defaultMobileAction()
    {
        $app_settings_model = new waAppSettingsModel();
        $this->view->assign('header_items', $this->getHeaderItems());
        $this->view->assign('company_name', htmlspecialchars($app_settings_model->get('webasyst', 'name', _ws('My company')), ENT_QUOTES, 'utf-8'));
        $this->view->assign('backend_url', $this->getConfig()->getBackendUrl(true));
        $this->view->assign('public_dashboards', $this->getPublicDashboards());
        $this->view->assign('counts', wa()->getStorage()->read('apps-count'));
        $this->dashboardAction();
    }

    public function defaultAction()
    {
        // url of kind <backend_url>/webasyst/ without any extra url parameters is not supported, just redirect it to <backend_url>/
        // if not redirect it could cause some other problems with url building
        $config = wa('webasyst')->getConfig();
        $request_url = $config->getRequestUrl();
        $parts = explode('/', trim($request_url, '/'));

        if (count($parts) == 2 && $parts[1] === 'webasyst') {
            $this->redirect($config->getBackendUrl(true));
        }

        // other part of default action

        $this->action = 'dashboard';
        $this->setLayout(new webasystBackendLayout());
        $this->view->assign("username", wa()->getUser()->getName());
        $this->view->assign('header_items', $this->getHeaderItems());
        $this->dashboardAction();
    }

    public function dashboardMobileAction()
    {
        $this->dashboardAction();
    }

    public function dashboardAction()
    {
        $widget_model = new waWidgetModel();
        $locale = wa()->getUser()->getLocale();

        // Create dashboard widgets on first login
        wa('webasyst')->getConfig()->initUserWidgets();

        // fetch widgets
        $rows = $widget_model->getByContact($this->getUserId());
        $widgets = array();
        foreach ($rows as $row) {
            if (($row['app_id'] == 'webasyst') || $this->getUser()->getRights($row['app_id'], 'backend')) {
                $app_widgets = wa($row['app_id'])->getConfig()->getWidgets();
                if (isset($app_widgets[$row['widget']])) {
                    $row['size'] = explode('x', $row['size']);
                    $row = $row + $app_widgets[$row['widget']];
                    if (!empty($row['rights'])) {
                        if (!waWidget::checkRights($row['rights'])) {
                            continue;
                        }
                    }
                    $row['href'] = wa()->getAppUrl($row['app_id'])."?widget={$row['widget']}&id={$row['id']}";
                    foreach ($row['sizes'] as $s) {
                        if ($s == array(1, 1)) {
                            $row['has_sizes']['small'] = true;
                        } elseif ($s == array(2, 1)) {
                            $row['has_sizes']['medium'] = true;
                        } elseif ($s == array(2, 2)) {
                            $row['has_sizes']['big'] = true;
                        }
                    }
                    $widgets[$row['block']][] = $row;
                }
            }
        }


        // activity stream
        $activity_action = new webasystDashboardActivityAction();
        $user_filters = wa()->getUser()->getSettings('webasyst', 'dashboard_activity');
        if ($user_filters) {
            $user_filters = explode(',', $user_filters);
        } else {
            $user_filters = array();
        }
        $activity = $activity_action->getLogs(array(
            'app_id' => ifempty($user_filters),
        ), $count);
        $activity_load_more = $count == 50;

        $this->view->assign('datetime_group', '');

        $today_users = (new webasystTodayUsers())->getGroups();
        if ($today_users) {
            $this->view->assign('datetime_group', $activity_action->getDatetimeGroup(date('Y-m-d')));
        }

        $row = reset($activity);
        $last_datetime_activity = isset($row['datetime']) ? $row['datetime'] : '';

        $no_today_activity = true;
        if (date('Y-m-d', strtotime($last_datetime_activity)) === date('Y-m-d')) {
            $no_today_activity = false;
        }

        if ($activity && waRequest::isXMLHttpRequest()) {
            $no_today_activity = true;
            $this->view->assign('datetime_group', $activity_action->getDatetimeGroup($last_datetime_activity));
        }

        $is_admin = wa()->getUser()->isAdmin('webasyst');

        $this->view->assign([
            'current_app'        => wa()->getApp(),
            'today_users'        => $today_users,
            'logo'               => (new webasystLogoSettings())->get(),
            'counts'             => wa()->getStorage()->read('apps-count'),
            'root_url'           => wa()->getRootUrl(),
            'widgets'            => $widgets,
            'apps'               => wa()->getUser()->getApps(),
            'backend_url'        => $this->getConfig()->getBackendUrl(true),
            'user'               => wa()->getUser(),
            'user_filters'       => $user_filters,
            'activity_load_more' => $activity_load_more,
            'activity'           => $activity,
            'is_admin'           => $is_admin,
            'notifications'      => $this->getAnnouncements(),
            'request_uri'        => waRequest::server('REQUEST_URI'),
            'show_tutorial'      => !wa()->getUser()->getSettings('webasyst', 'widget_tutorial_closed'),
            'public_dashboards'  => $this->getPublicDashboards(),
            'no_today_activity'  => $no_today_activity,
        ]);
    }

    public function logoutAction()
    {
        $this->logAction('logout', wa()->getEnv());
        // Clear auth data
        $this->getUser()->logout();

        // Redirect to the main page
        $this->redirect($this->getConfig()->getBackendUrl(true));
    }

    /**
     * Userpic
     */
    public function photoAction()
    {
        $id = (int)waRequest::get('id');
        if (!$id) {
            $id = $this->getUser()->getId();
        }

        $contact = new waContact($id);
        $rand = $contact['photo'];
        $file = wa()->getDataPath(waContact::getPhotoDir($id)."$rand.original.jpg", TRUE, 'contacts');

        $size = waRequest::get('size');
        if (!file_exists($file)) {
            $size = (int)$size;
            if (!in_array($size, array(20, 32, 50, 96))) {
                $size = 96;
            }
            waFiles::readFile($this->getConfig()->getRootPath().'/wa-content/img/userpic'.$size.'.jpg');
        } else {
            // original file
            if ($size == 'original') {
                waFiles::readFile($file);
            }
            // cropped file
            elseif ($size == 'full') {
                $file = str_replace('.original.jpg', '.jpg', $file);
                waFiles::readFile($file);
            }
            // thumb
            else {
                if (!$size) {
                    $size = '96x96';
                }
                $size_parts = explode('x', $size, 2);
                $size_parts[0] = (int)$size_parts[0];
                if (!isset($size_parts[1])) {
                    $size_parts[1] = $size_parts[0];
                } else {
                    $size_parts[1] = (int)$size_parts[1];
                }

                if (!$size_parts[0] || !$size_parts[1]) {
                    $size_parts = array(96, 96);
                }

                $size = $size_parts[0].'x'.$size_parts[1];

                $thumb_file = str_replace('.original.jpg', '.'.$size.'.jpg', $file);
                $file = str_replace('.original.jpg', '.jpg', $file);

                if (!file_exists($thumb_file) || filemtime($thumb_file) < filemtime($file)) {
                    waImage::factory($file)->resize($size_parts[0], $size_parts[1])->save($thumb_file);
                    clearstatcache();
                }
                waFiles::readFile($thumb_file);
            }
        }
    }

    public function run($params = null)
    {
        $action = $params;
        if (!$action) {
            $action = 'default';
        }

        wa('webasyst')->event('backend_dashboard_before_action', ref([
            'action' => $action,
        ]));

        return parent::run($action);
    }

    protected function getPublicDashboards()
    {
        $is_admin = wa()->getUser()->isAdmin('webasyst');

        $public_dashboards = [];
        if ($is_admin) {
            $dashboard_model = new waDashboardModel();
            $public_dashboards = $dashboard_model->order('name')->fetchAll('id');
        }

        return $public_dashboards;
    }
}

