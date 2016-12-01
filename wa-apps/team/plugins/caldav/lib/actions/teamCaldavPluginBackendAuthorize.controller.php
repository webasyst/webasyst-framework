<?php

class teamCaldavPluginBackendAuthorizeController extends waJsonController
{
    public function execute()
    {
        $url = $this->getRequest()->post('url');
        $login = $this->getRequest()->post('login');
        $password = $this->getRequest()->post('password');

        $parsed = parse_url($url);
        $scheme = ifset($parsed['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            $this->errors = array(
                'code' => 0,
                'message' => ''
            );
            return;
        }

        /**
         * @var teamCaldavPlugin $plugin
         */
        $plugin = teamCalendarExternalPlugin::factory('caldav');
        try {
            $plugin->authorize($url, $login, $password);
        } catch (waException $e) {
            $this->errors = array(
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            );
        }
    }
}