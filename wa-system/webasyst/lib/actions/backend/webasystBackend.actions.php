<?php

class webasystBackendActions extends waViewActions
{

    public function defaultMobileAction()
    {
        $apps = $this->getUser()->getApps();
        $this->view->assign('apps', $apps);
        $backend_url = $this->getConfig()->getBackendUrl(true);
        $this->view->assign('backend_url', $backend_url);
    }

    public function defaultAction()
    {
        try {
            $dashboard_type = waRequest::get('dashboard_type');

            $this->setLayout(new webasystBackendLayout());

            $this->view->assign("username", wa()->getUser()->getName());

            $this->dashboardAction();
            $this->action = 'dashboard';
            return;

            $template_file = wa()->getDataPath('templates/BackendDefault.html', false, 'webasyst');
            if (file_exists($template_file)) {
                $this->template = 'file:'.$template_file;
            }
        } catch (waException $e) {
            throw $e;
            // user not exists
            if ($e->getCode() == 404) {
                wa()->getUser()->logout();
                wa()->dispatch();
                exit;
            }
        }
    }

    public function dashboardAction()
    {
        $widget_model = new waWidgetModel();
        $locale = wa()->getUser()->getLocale();

        // Create dashboard widgets on first login
        if (!wa()->getUser()->getSettings('webasyst', 'dashboard')) {
            $apps = wa()->getApps(true);
            $widgets = array();
            foreach ($apps as $app_id => $app) {
                if (($app_id == 'webasyst') || $this->getUser()->getRights($app_id, 'backend')) {
                    foreach (wa($app_id)->getConfig()->getWidgets() as $w_id => $w) {
                        if (!empty($w['locale']) && ($w['locale'] != $locale)) {
                            continue;
                        }
                        $widgets[] = $w;
                    }
                }
            }
            $block = 0;
            $contact_id = wa()->getUser()->getId();
            foreach ($widgets as $w) {
                $max_size = $w['sizes'][0];
                foreach ($w['sizes'] as $s) {
                    if ($s[0] + $s[1] > $max_size[0] + $max_size[1]) {
                        $max_size = $s;
                    }
                }

                $row = array(
                    'app_id' => $w['app_id'],
                    'widget' => $w['widget'],
                    'name' => $w['name'],
                    'block' => $block++,
                    'sort' => 0,
                    'size' => $max_size[0] . 'x' . $max_size[1],
                    'contact_id' => $contact_id,
                    'create_datetime' => date('Y-m-d H:i:s')
                );
                $widget_model->insert($row);
            }
            wa()->getUser()->setSettings('webasyst', 'dashboard', 1);
        }

        // fetch widgets
        $rows = $widget_model->getByContact($this->getUserId());
        $widgets = array();
        foreach ($rows as $row) {
            if (($row['app_id'] == 'webasyst') || $this->getUser()->getRights($row['app_id'], 'backend')) {
                $app_widgets = wa($row['app_id'])->getConfig()->getWidgets();
                if (isset($app_widgets[$row['widget']])) {
                    $row['size'] = explode('x', $row['size']);
                    $row = $row + $app_widgets[$row['widget']];
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
        $this->view->assign('widgets', $widgets);

        // announcement
        $user = wa()->getUser();
        $announcement_model = new waAnnouncementModel();
        $apps = $user->getApps();
        $data = $announcement_model->getByApps($user->getId(), array_keys($apps), $user['create_datetime']);
        $announcements = array();
        $announcements_apps = array();
        foreach ($data as $row) {
            // show no more than 1 message per application
            if (!empty($announcements_apps[$row['app_id']])) {
                continue;
            }
            $announcements_apps[$row['app_id']] = true;
            $announcements[] = $row;
        }
        $this->view->assign('notifications', $announcements);

        // activity stream
        $activity_action = new webasystDashboardActivityAction();
        $this->view->assign('apps', wa()->getUser()->getApps());
        $user_filters = wa()->getUser()->getSettings('webasyst', 'dashboard_activity');
        if ($user_filters) {
            $user_filters = explode(',', $user_filters);
        } else {
            $user_filters = array();
        }
        $this->view->assign('user_filters', $user_filters);
        $this->view->assign('activity', $activity_action->getLogs(array(), $count));
        if ($count == 50) {
            $this->view->assign('activity_load_more', true);
        }

        // Whether to show tutorial
        $this->view->assign('show_tutorial', !wa()->getUser()->getSettings('webasyst', 'widget_tutorial_closed'));
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
}

