<?php

class teamCaldavPluginBackendUrlController extends waJsonController
{
    public function execute()
    {
        $type = $this->getRequest()->request('type');
        if ($type === 'yandex') {
            $this->response = array(
                'url' => 'https://caldav.yandex.ru/',
            );
        } else if ($type === 'yahoo') {
            $this->response = array(
                'url' => 'https://caldav.calendar.yahoo.com'
            );
        } else if ($type === 'fruux') {
            $this->response = array(
                'url' => 'https://dav.fruux.com'
            );
        } else if ($type === 'icloud') {

            $calendar_id = (int) $this->getRequest()->request('id');

            /**
             * @var teamCaldavPlugin $plugin
             */
            $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar_id);
            if (!$plugin) {
                throw new waException("Plugin not found");
            }

            $number = (int) $this->getRequest()->request('number');
            if ($number <= 0) {
                $number = 1;
            }

            $max_number = 24;
            if ($number >= $max_number) {
                $number -= 1;   // to get server response status to send error to client
            }

            $login = $this->getRequest()->request('login');
            $password = $this->getRequest()->request('password');

            $max_execution_time = (int) ini_set('max_execution_time', 30);
            if ($max_execution_time <= 0) {
                $max_execution_time = 30;
            }
            $max_tries = 5;

            $start_time = time();

            $url = '';
            $error = array();

            for ($try = 0; $try < $max_tries && $number <= $max_number; $try += 1, $number += 1) {

                $url = $this->buildICloudServerUrl($number);
                $error = $this->testICloudServer($url, $login, $password);
                if (!$error) {
                    break;
                }

                $elapsed_time = time() - $start_time;
                if ($max_execution_time - $elapsed_time <= 5) {
                    break;
                }
            }

            if (!$error) {
                $this->response = array(
                    'number' => $number,
                    'url' => $url
                );
                return;
            }

            if ($number >= $max_number) {
                $this->errors = $error;
                return;
            }

            $this->response = array(
                'number' => $number
            );

        }
    }

    protected function buildICloudServerUrl($number)
    {
        $server_url = "https://p".str_pad($number, 2, '0', STR_PAD_LEFT)."-caldav.icloud.com";
        return $server_url;
    }

    protected function testICloudServer($server_url, $login, $password)
    {
        $client = new teamCaldavClient($server_url, $login, $password);
        try {
            $client->checkConnection();
        } catch (teamCaldavClientException $e) {
            return array(
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            );
        }
        return array();
    }
}