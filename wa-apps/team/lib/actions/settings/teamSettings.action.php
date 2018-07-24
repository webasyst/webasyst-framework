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

        $this->view->assign(array(
            'calendars' => teamCalendar::getCalendars(false),
            'users' => teamHelper::getUsers(),
            'user_name_formats' => $this->getUserNameFormats(),
        ));
    }

    protected function getUserNameFormats()
    {
        $formats = $this->getConfig()->getUsernameFormats();
        $tasm = $this->getSettingsModel();
        $current_format = $tasm->getUserNameDisplayFormat();
        foreach ($formats as &$format) {
            $format['selected'] = $format['format'] === $current_format;
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
}
