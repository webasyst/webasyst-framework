<?php

class installerLicensesAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setLayout(new installerBackendStoreLayout());
    }

    public function execute()
    {
        $id = $this->getRequest()->param('id', 0, waRequest::TYPE_INT);
        $is_auto_install = $id > 0 && (bool)$this->getRequest()->get('install');

        list($licenses, $error) = $this->getLicenses($id);

        $total_count = count($licenses);

        $product_types = $this->getProductTypes($licenses);

        $type = $this->getProductType();
        $this->filterLicenses($licenses, $type);

        $this->extendByInstalledFlags($licenses);
        $this->extendByCheckedRequirements($licenses);

        $this->view->assign([
            'total_count' => $total_count,
            'product_types' => $product_types,
            'licenses' => $licenses,
            'error' => $error,
            'current_product_type' => $type,
            'is_auto_install' => $is_auto_install
        ]);
    }

    protected function getProductType()
    {
        return $this->getRequest()->get('type', '', waRequest::TYPE_STRING_TRIM);
    }

    protected function getProductTypes(array $licenses)
    {
        $names = [
            'APP' => _w('Applications'),
            'THEME' => _w('Themes'),
            'PLUGIN' => _w('Plugins'),
            'WIDGET' => _w('Widgets')
        ];
        $types = waUtils::getFieldValues($licenses, 'type');

        return waUtils::extractValuesByKeys($names, $types);
    }

    /**
     * Extend each license item with 'is_installed' flag
     * @param $licenses
     * @throws waException
     */
    protected function extendByInstalledFlags(&$licenses)
    {
        if (!$licenses) {
            return;
        }

        $apps = [];
        $types = [];
        foreach ($licenses as $license) {
            $allow_to_rebind = !$license['bind_available_date'] || !$license['bound_to'];
            if ($allow_to_rebind) {
                $types[] = $license['type'];
                $apps[] = $license['app_id'];
            }
        }

        if (!$apps) {
            return;
        }

        $apps = array_unique($apps);

        // Installed apps (+ wa-plugins/payment, wa-plugins/shipping, wa-plugins/sms)
        $assets = installerHelper::getInstaller()->getApps([
            'installed' => true,
            'system' => true
        ]);

        // Get extras only required types
        $types = array_unique($types);
        $types = waUtils::extractValuesByKeys(['PLUGIN' => 'plugins', 'WIDGET' => 'widgets', 'THEME' => 'themes'], $types);

        $options = array(
            'local' => true,
            'status' => false,
            'installed' => true,
            'system' => true,   // system plugins
        );

        foreach ($types as $type) {
            $extras = installerHelper::getInstaller()->getExtras($apps, $type, $options);
            foreach ($extras as $app_id => $extras_item) {
                if (isset($assets[$app_id]) && !empty($extras_item[$type])) {
                    unset($assets[$app_id][$type]);
                    $assets[$app_id] += $extras_item;
                }
            }
        }

        foreach ($licenses as &$license) {
            $app_id = $license['app_id'];
            $ext_id = $license['ext_id'];

            $is_installed = false;
            if ($license['type'] === 'APP') {
                $is_installed = isset($assets[$app_id]);
            } elseif ($license['type'] === 'PLUGIN') {
                $is_installed = isset($assets[$app_id]['plugins'][$ext_id]);
            } elseif ($license['type'] === 'WIDGET') {
                $is_installed = isset($assets[$app_id]['widgets'][$ext_id]);
            } elseif ($license['type'] === 'THEME') {
                $is_installed = isset($assets[$app_id]['themes'][$ext_id]);
            }

            $license['is_installed'] = $is_installed;
        }
        unset($license);
    }

    /**
     * Extend license item with 'requirements_warnings' of type string[]
     * @param $licenses
     */
    protected function extendByCheckedRequirements(&$licenses)
    {
        $product_requirements = [];
        foreach ($licenses as $license) {
            $requirements = $license['requirements'];
            $product_id = $license['product_id'];
            $product_requirements[$product_id] = [
                'product_id' => $product_id,
                'requirements' => $requirements
            ];
        }

        $product_requirements = array_values($product_requirements);

        $checker = new installerRequirementsChecker($product_requirements);
        $warnings = $checker->check();

        foreach ($licenses as &$license) {
            $product_id = $license['product_id'];
            $license['requirements_warnings'] = [];
            if (isset($warnings[$product_id])) {
                $license['requirements_warnings'] = $warnings[$product_id];
            }
        }
        unset($license);
    }

    /**
     * @param int|int[] $id [optional] - if missed get all licenses
     * @return array
     * @throws waException
     */
    protected function getLicenses($id = null)
    {
        $api = new installerWebasystIDApi();

        $ids = waUtils::toIntArray($id);
        $ids = waUtils::dropNotPositive($ids);

        $options = [];
        if ($ids) {
            $options['params'] = [
                'id' => $ids
            ];
        }

        $result = $api->getLicenses($this->getUserId(), $options);

        if (!$result['status']) {
            switch ($result['details']['error']) {
                case 'system_error':
                    $error = _w('System error');
                    break;
                default:
                    $error = $result['details']['error'] ? $result['details']['error'] : _w('Unknown error while trying to get licenses.');
                    break;
            }
            return [
                [],
                $error
            ];
        }

        $licenses = [];
        foreach ($result['details']['licenses'] as $license) {
            $licenses[$license['license_id']] = $license;
        }

        return [
            $licenses,
            '',
        ];
    }

    protected function filterLicenses(&$licenses, $type)
    {
        foreach ($licenses as $index => $license) {
            $product_type = $license['type'];
            if ($type && $type !== $product_type) {
                unset($licenses[$index]);
            }
        }
    }
}
