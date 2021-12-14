<?php
class teamSettingsAction extends teamContentViewAction
{
    /**
     * @var teamWaAppSettingsModel
     */
    private $sm;

    public function execute()
    {
        if (!teamHelper::hasRights()) {
            throw new waRightsException();
        }

        $is_waid_forced = $this->isWaidForced();
        
        $this->view->assign(array(
            'calendars' => teamCalendar::getCalendars(false),
            'users' => teamHelper::getUsers(),
            'user_name_formats' => $this->getUserNameFormats($is_waid_forced),
            'is_waid_forced' => $is_waid_forced,
        ));
    }

    protected function getUserNameFormats($is_waid_forced)
    {
        $formats = $this->getConfig()->getUsernameFormats();
        $tasm = $this->getSettingsModel();
        $current_format = $tasm->getUserNameDisplayFormat();
        foreach ($formats as &$format) {
            $format['selected'] = $format['format'] === $current_format;
            $format['disabled'] = $format['format'] === 'login' && $is_waid_forced;
        }
        unset($format);
        return $formats;
    }

    protected function getSettingsModel()
    {
        if (!$this->sm) {
            $this->sm = new teamWaAppSettingsModel();
        }
        return $this->sm;
    }

    protected function isWaidForced()
    {
        $cm = new waWebasystIDClientManager();
        return $cm->isBackendAuthForced();
    }
}
