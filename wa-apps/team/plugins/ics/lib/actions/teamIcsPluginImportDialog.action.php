<?php

class teamIcsPluginImportDialogAction extends waViewAction
{
    public function execute()
    {
        $tcm = new teamWaContactCalendarsModel();
        $inner_calendars = $tcm->getCalendars();

        $this->view->assign(array(
            'inner_calendars' => $inner_calendars,
        ));

        $this->setTemplate($this->getPluginRoot().'templates/ImportDialog.html');
    }
}