<?php

/**
 * Hearing
 */
class installerWebasystBackend_headerHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $header_top = $this->getAnnouncements();
        return array('header_top' => $header_top);
    }

    protected function getAnnouncements()
    {
        if (!wa()->getUser()->getRights('installer')) {
            return '';
        }
        $wasm = new waAppSettingsModel();
        $wcsm = new waContactSettingsModel();
        $sql = "SELECT a.name, a.value FROM {$wasm->getTableName()} a
            LEFT JOIN {$wcsm->getTableName()} c ON c.name=a.name AND c.app_id='installer' AND c.contact_id=".intval(wa()->getUser()->getId())
            ." WHERE a.app_id='installer' AND a.name LIKE 'a-%' AND c.value IS NULL
            ORDER BY name";
        $announcements = $wasm->query($sql)->fetchAll('name');

        $view = wa('installer')->getView();
        $view->assign(array(
            'announcements' => $announcements,
        ));
        return $view->fetch(
            wa('installer')->getAppPath('lib/handlers/templates/webasyst.backend_header.announcement.html', 'installer')
        );
    }
}
