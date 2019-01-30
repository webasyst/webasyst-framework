<?php

class teamDefaultLayout extends waLayout
{
    protected $js = array();
    protected $hide_sidebar = false;

    public function __construct($hide_sidebar = false)
    {
        parent::__construct();
        $this->hide_sidebar = $hide_sidebar;
    }

    public function execute()
    {
        if (!$this->hide_sidebar) {
            $this->executeAction('sidebar', new teamSidebarAction());
        }

        /**
         * Include plugins js and css
         * @event backend_assets
         * @return array[string]string $return[%plugin_id%]
         */
        $this->view->assign('backend_assets', wa()->event('backend_assets'));

        $tasm = new teamWaAppSettingsModel();
        $this->view->assign(array(
            'is_debug' => (int) waSystemConfig::isDebug(),
            'map_info' => $tasm->getMapInfo()
        ));
    }
}
