<?php

class teamOffice365PluginBackendRenewTokenAction extends waViewAction
{
    public function execute()
    {
        $calendar_id = (int) $this->getRequest()->post('id');
        if ($calendar_id <= 0) {
            $this->calendarNotFound();
        }

        $cem = new teamCalendarExternalModel();
        $calendar = $cem->getCalendar($calendar_id);
        if (!$calendar) {
            $this->calendarNotFound();
        }

        if ($calendar['contact_id'] != wa()->getUser()->getId()) {
            $this->accessDenied();
        }

        /**
         * @var teamOffice365Plugin $plugin
         */
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);
        if (!$plugin || !($plugin instanceof teamOffice365Plugin)) {
            $this->pluginNotFound();
        }

        die($plugin->authorizeBegin($calendar['id']));
    }

    public function calendarNotFound()
    {
        throw new waException(_wp('Unknown calendar'));
    }

    public function pluginNotFound()
    {
        throw new waException(_wp('Plugin not found'));
    }

    public function accessDenied()
    {
        throw new waRightsException(_w('Access denied'));
    }
}