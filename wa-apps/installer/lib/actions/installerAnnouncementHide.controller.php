<?php

class installerAnnouncementHideController extends waController
{
    public function execute()
    {
        $key = waRequest::post('key');

        $announcement = $this->getAnnouncement($key);
        if ($this->canClose($announcement)) {
            $this->close($key);
            die('ok');
        }
        die('disallowed');
    }

    private function canClose($announcement)
    {
        $always_open = $announcement && $announcement['always_open'];
        if ($always_open) {
            return !$this->isProductLeaseBlocked(); // server now allow always open banner to close
        }
        return true;
    }

    private function isProductLeaseBlocked()
    {
        $installer = installerHelper::getInstaller();

        $domain = $installer->getDomain();
        $hash = $installer->getHash();
        $url = $installer->getCheckProductLeaseStatusUrl();

        $app_id = $this->getRequest()->post('app_id');

        $net_options = [
            'timeout' => 20,
            'format' => waNet::FORMAT_JSON,
            'request_format' => waNet::FORMAT_RAW,
            'expected_http_code' => null
        ];

        $net = new waNet($net_options);

        try {
            $params = [
                'domain' => $domain,
                'hash' => $hash,
                'slug' => $app_id,
            ];

            $response = $net->query($url, $params, waNet::METHOD_POST);

            $data = is_array($response) && isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
            if (!$data) {
                return false;
            }

            $statuses = ifset($data['statuses']);
            $statuses = is_array($statuses) ? $statuses : [];

            $status = isset($statuses[$app_id]['status']) ? $statuses[$app_id]['status'] : 'unknown';

            return $status === 'blocked';

        } catch (waException $e) {

        }

        return false;
    }

    private function close($key)
    {
        $wcsm = new waContactSettingsModel();
        $wcsm->replace(array(
            'contact_id' => wa()->getUser()->getId(),
            'app_id'     => 'installer',
            'name'       => $key,
            'value'      => 1,
        ));
    }

    private function getAnnouncement($key)
    {
        return (new installerAnnouncementList)->getOne($key);
    }
}
