<?php

class installerLicensesBindController extends waJsonController
{
    public function execute()
    {
        $id = $this->getRequest()->post('id');
        if ($id <= 0) {
            $this->errors = _w('Invalid license ID');
            return;
        }

        $license = $this->getLicense($id);
        if (!$license) {
            $this->errors = _w('License not found.');
            return;
        }

        $warnings = $this->checkRequirements($license);
        if ($warnings) {
            $this->errors = $warnings;
            return;
        }

        $this->doBind($id);
    }

    protected function checkRequirements(array $license)
    {
        $product_id = $license['product_id'];

        $checker = new installerRequirementsChecker([
            [
                'product_id' => $product_id,
                'requirements' => $license['requirements']
            ]
        ]);

        $warnings = $checker->check();
        return $warnings && isset($warnings[$product_id]) ? $warnings[$product_id] : [];
    }

    protected function doBind($id)
    {
        $hash = installerHelper::getHash();
        $domain = installerHelper::getDomain();
        $token = $this->getStoreToken();

        $result = $this->bindLicense([
            'token' => $token,
            'hash' => $hash,
            'domain' => $domain,
            'license_id' => $id
        ]);

        if (!$result['status']) {
            $this->errors = $result['details']['error'];
            switch ($this->errors) {
                case 'bind_not_available':
                    $this->errors = _w('License binding not available.');
                    break;
                case '':
                    $this->errors = _w('Unknown error while trying to bind the license.');
                    break;
            }
            return;
        }
    }

    protected function getStoreToken()
    {
        $asm = new waAppSettingsModel();
        $token_data = $asm->get('installer', 'token_data', false);
        if ($token_data) {
            $token_data = waUtils::jsonDecode($token_data, true);
            return $token_data && isset($token_data['token']) ? $token_data['token'] : null;
        }
        return null;
    }

    protected function bindLicense(array $params = [])
    {
        $api = new installerWebasystIDApi();
        return $api->bindBindLicense($this->getUserId(), $params);
    }

    protected function getLicense($id)
    {
        $api = new installerWebasystIDApi();
        $result = $api->getLicenses($this->getUserId());
        if (!$result['status']) {
            return null;
        }

        $licenses = $result['details']['licenses'];
        foreach ($licenses as $license) {
            if ($license['license_id'] == $id) {
                return $license;
            }
        }

        return null;
    }

}
