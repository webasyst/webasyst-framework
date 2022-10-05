<?php

class apiexplorerBackendAction extends waViewAction
{
    protected $installer_apps;
    protected static $default_central_url = 'https://www.webasyst.com/my/api/explorer/';
    
    public function execute()
    {
        $this->view->assign('user_id', wa()->getUser()->getId());
        $this->view->assign('isAdmin', wa()->getUser()->isAdmin());
        $this->view->assign('central_url', $this->getCentralUrl());
    }

    protected function getCentralUrl()
    {
        $installer_apps = $this->getInstallerApps();
        if (!$installer_apps) {
            return self::$default_central_url;
        }
        $url = $installer_apps->getEndpointsUrl() . '?app=apibaza';
        $endpoints = $this->requestEndpoints($url);
        if (empty($endpoints) || empty(ifset($endpoints, 0, 'api', null))) {
            return self::$default_central_url;
        }
        return $endpoints[0]['api'];
    }

    protected function getInstallerApps()
    {
        if (!$this->installer_apps) {
            if (!class_exists('waInstallerApps')) {
                $autoload = waAutoload::getInstance();
                $autoload->add('waInstallerApps', 'wa-installer/lib/classes/wainstallerapps.class.php');
            }
            if (!class_exists('waInstallerApps')) {
                return null;
            }
            $this->installer_apps = new waInstallerApps();
        }
        return $this->installer_apps;
    }

    protected function requestEndpoints($url)
    {
        $options = [
            'timeout' => 30,
            'format' => waNet::FORMAT_JSON
        ];

        $net = new waNet($options);
        $response = null;
        try {
            $response = $net->query($url);
        } catch (Exception $e) {
            $this->logException($e);
            $this->logError([
                'url' => $url,
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return [];
        }

        // No response from API
        if (!$response) {
            return [];
        }

        // Error from API
        if (!isset($response['status']) || $response['status'] === 'fail') {
            return [];
        }

        // Expected response from API
        $correct_response = isset($response['data']['endpoints']) && is_array($response['data']['endpoints']);

        $endpoints = $correct_response ? $response['data']['endpoints'] : [];

        if (!$correct_response) {
            // Unexpected response
            $this->logError([
                'url' => $url,
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
        }

        return $endpoints;
    }
}
