<?php

class teamCalendarExternalAction extends teamContentViewAction
{
    public function execute()
    {
        $id = $this->getId();

        $calendar = null;
        if ($id) {
            $calendar = $this->getCalendar($id);
        }

        $error_key = 'team/calendar_external/' . $id . '/auth_failed';
        $error = $this->getStorage()->get($error_key);
        if ($error) {
            $this->getStorage()->del($error_key);
        }

        if (!$calendar || (!$calendar['is_connected'] && $calendar['is_own'])) {
            $this->connectCalendarExecute($calendar, array(
                'auth_error' => $error
            ));
            return;
        }

        if (!$calendar['is_own'] || ($calendar['is_mapped'] && $calendar['is_imported'])) {
            $this->infoCalendarExecute($calendar, array(
                'auth_error' => $error
            ));
            return;
        }

        try {
            $this->editCalendarExecute($calendar, array(
                'auth_error' => $error
            ));
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->infoCalendarExecute($calendar, array(
                'auth_error' => $error
            ));
            return;
        }
    }

    public function getId()
    {
        return (int) $this->getRequest()->get('id');
    }

    public function connectCalendarExecute($calendar = null, $assign = array())
    {
        $plugins = teamCalendarExternalPlugin::getPlugins();

        foreach ($plugins as &$plugin) {
            $plugin['checked'] = false;
            $plugin['enabled'] = true;
            if ($calendar) {
                if ($calendar['type'] != $plugin['id']) {
                    $plugin['enabled'] = false;
                } else {
                    $plugin['checked'] = true;
                }
            }

            $plugin_instance = teamCalendarExternalPlugin::factory($plugin['id']);
            $plugin['required_settings'] = !$plugin_instance->areAllRequiredSettingsFilled();

            if ($plugin['enabled']) {
                $plugin['enabled'] = !$plugin['required_settings'];
            }

        }
        unset($plugin);

        $this->view->assign(array(
            'calendar' => $calendar,
            'plugins' => $plugins,
            'mode' => 'connect'
        ));
        $this->view->assign($assign);
    }

    public function editCalendarExecute($calendar, $assign = array())
    {
        $current_user = $this->getUser();
        $has_access = $current_user->isAdmin('team') || $current_user->getId() == $calendar['contact_id'];
        if (!$has_access) {
            throw new waRightsException('Access denied');
        }
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar['id']);
        $calendar['plugin'] = array(
            'name' => $plugin->getCalendarName(),
            'account_info_html' => $plugin->getAccountInfoHtml(array('action' => 'edit')),
            'icon' => $plugin->getIconUrl()
        );
        $inner_calendars = $this->getInnerCalendars();
        $external_calendars = $this->getExternalCalendars($calendar);
        $this->view->assign($assign);
        $this->view->assign(array(
            'id' => $this->getId(),
            'redirect_url' => $this->getRedirectUrl(),
            'calendar' => $calendar,
            'inner_calendars' => $inner_calendars,
            'external_calendars' => $external_calendars,
            'can_map' => $current_user->getId() == $calendar['contact_id'],
            'integration_levels' => $this->getIntegrationLevels($calendar),
            'mode' => 'edit'
        ));
    }

    public function infoCalendarExecute($calendar, $assign = array())
    {
        $current_user = $this->getUser();
        $has_access = $current_user->isAdmin('team') || $current_user->getId() == $calendar['contact_id'];
        if (!$has_access) {
            throw new waRightsException('Access denied');
        }
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar['id']);
        $calendar['plugin'] = array(
            'name' => $plugin->getCalendarName(),
            'account_info_html' => $plugin->getAccountInfoHtml(array('action' => 'info')),
            'icon' => $plugin->getIconUrl()
        );
        $calendar['name'] = $plugin->getCalendarName();

        $ccm = new waContactCalendarsModel();
        $wa_calendar = $ccm->getById($calendar['calendar_id']);
        if (!$wa_calendar) {
            $wa_calendar = $ccm->getEmptyRow();
        }
        $calendar['calendar'] = $wa_calendar;
        $calendar['owner'] = new waContact($calendar['contact_id']);

        $this->view->assign($assign);
        $this->view->assign(array(
            'calendar' => $calendar,
            'is_admin' => wa()->getUser()->isAdmin('team'),
            'mode' => 'info'
        ));
    }

    public function getCalendar($id)
    {
        $cem = new teamCalendarExternalModel();
        $calendar = $cem->getCalendar($id);
        if (!$calendar) {
            throw new waException(_w('External calendar not found'));
        }
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);
        $calendar = $plugin->getCalendar()->toArray();
        $calendar['is_connected'] = $plugin->isConnected();
        $calendar['is_mapped'] = $plugin->isMapped();
        $calendar['is_imported'] = $plugin->isImported();
        $calendar['is_own'] = $calendar['contact_id'] == wa()->getUser()->getId();
        return $calendar;
    }

    public function getInnerCalendars()
    {
        $tcm = new teamWaContactCalendarsModel();
        return $tcm->getCalendars();
    }

    public function getExternalCalendars($calendar)
    {
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);
        return $plugin->getCalendars();
    }

    public function getIntegrationLevels($calendar)
    {
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);

        $levels = teamCalendarExternalModel::getIntegrationLevels(true);
        foreach ($levels as $level => &$level_info) {
            $levels[$level]['disabled'] = true;
        }
        unset($level_info);

        $integration_level = $plugin->getIntegrationLevel();
        if (!isset($levels[$integration_level])) {
            $integration_level = teamCalendarExternalModel::INTEGRATION_LEVEL_SUBSCRIPTION;
        }

        foreach ($levels as $level => &$level_info) {
            $level_info['disabled'] = false;
            if ($integration_level == $level) {
                break;
            }
        }
        unset($level_info);

        return $levels;
    }

    public function getRedirectUrl()
    {
        return wa('team')->getUrl(true) . "u/" . wa()->getUser()->get('login') . '/';
    }
}
