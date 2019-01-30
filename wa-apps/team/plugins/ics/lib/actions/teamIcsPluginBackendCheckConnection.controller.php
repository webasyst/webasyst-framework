<?php

class teamIcsPluginBackendCheckConnectionController extends waJsonController
{
    public function execute()
    {
        $post = wa()->getRequest()->post();

        /**
         * @var teamIcsPlugin $plugin
         */
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($post['id']);
        if (!$plugin) {
            throw new waException('Plugin not found');
        }

        try {
            $plugin->checkConnection($post);
        } catch (teamCalendarExternalAuthorizeFailedException $e) {
            $this->errors = array(
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            );
        }
    }
}