<?php

class installerWebasystWaid_authHandler extends waEventHandler
{
    /**
     * @param array $params
     *      string $params['type'] - 'backend', 'invite, 'bind', ...
     *      array|null $params['dispatch']
     *          string $params['dispatch']['app']
     *          string $params['dispatch']['module']
     *          string $params['dispatch']['action'] [optional]
     *          ... other key-value pairs
     * @return array|array[][]|string[][]|void
     * @throws waException
     */
    public function execute(&$params)
    {
        $type = isset($params['type']) ? $params['type'] : '';
        if ($type != 'backend') {
            return [];
        }

        $dispatch = $params['dispatch'];
        if (!$dispatch || $dispatch['app'] !== 'installer') {
            // doesnt' even try - because dispatch is not applicable to this hook call
            return [];
        }

        $module = $dispatch['module'];
        $action = isset($dispatch['action']) ? $dispatch['action'] : '';

        if (empty($dispatch['install'])) {
            return;
        }

        if ($module === 'licenses') {
            return $this->dispatchToInstallLicense($dispatch);
        }

        if ($module === 'store' && $action === 'product') {
            return $this->dispatchToInstallProduct($dispatch);
        }

        return [];

    }

    protected function dispatchToInstallProduct(array $dispatch)
    {
        $url = isset($dispatch['url']) ? $dispatch['url'] : '';
        if (!$url) {
            return [];
        }

        // Sorry current backend user has not access to installer
        if (!wa()->getUser()->isAdmin('installer')) {
            return [
                'dispatch' => [
                    'error' => [
                        'code' => 'access_denied',
                        'message' => _w('Product installation is allowed only to users with admin access to Installer app.')
                    ]
                ]
            ];
        }

        $redirect_url = wa()->getConfig()->getRootUrl(true) . wa()->getConfig()->getBackendUrl()."/installer/store/{$url}/?install=1";
        return [
            'dispatch' => [
                'url' => $redirect_url
            ]
        ];
    }

    protected function dispatchToInstallLicense(array $dispatch)
    {
        // expected license ID
        $license_id = isset($dispatch['id']) ? $dispatch['id'] : 0;
        $license_id = wa_is_int($license_id) && $license_id > 0 ? $license_id : 0;
        if ($license_id <= 0) {
            return [];
        }

        // Sorry current backend user has not access to installer
        if (!wa()->getUser()->isAdmin('installer')) {
            return [
                'dispatch' => [
                    'error' => [
                        'code' => 'access_denied',
                        'message' => _w('License binding is allowed only to users with admin access to Installer app.')
                    ]
                ]
            ];
        }

        $redirect_url = wa()->getConfig()->getRootUrl(true) . wa()->getConfig()->getBackendUrl()."/installer/licenses/{$license_id}/?install=1";
        return [
            'dispatch' => [
                'url' => $redirect_url
            ]
        ];
    }

}
