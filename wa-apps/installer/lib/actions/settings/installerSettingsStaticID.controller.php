<?php

class installerSettingsStaticIDController extends waJsonController
{
    public function execute()
    {
        $this->response = $this->doQuery();
    }

    protected function doQuery()
    {
        $options = [
            'timeout' => 30,
            'format' => waNet::FORMAT_JSON
        ];

        $net = new waNet($options);

        $response = null;
        try {
            $response = $net->query($this->getUrl());
        } catch (Exception $e) {
            $this->logException($e);
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return null;
        }

        if (!$response) {
            $this->logError('empty response');
            return [
                'id' => '',
                'beta_test_products' => []
            ];
        }

        if (empty($response['status']) || empty($response['data'])) {
            $this->logError('fail response');
            return [
                'id' => '',
                'beta_test_products' => []
            ];
        }

        $id = isset($response['data']['id']) ? $response['data']['id'] : '';
        $beta_test_products = isset($response['data']['beta_test_products']) ? $response['data']['beta_test_products'] : [];

        $this->workupBetaTestProducts($beta_test_products);

        return [
            'id' => $id,
            'beta_test_products' => $beta_test_products
        ];
    }

    protected function workupBetaTestProducts(array &$beta_test_products)
    {
        foreach ($beta_test_products as &$product) {
            $datetime = !empty($product['beta_test_create_datetime']) ? $product['beta_test_create_datetime'] : null;
            $product['beta_test_create_date_formatted'] = null;
            if ($datetime) {
                $product['beta_test_create_date_formatted'] = wa_date('humandate', $datetime);
            }
        }
        unset($product);
    }

    protected function getUrl()
    {
        $wa_installer = installerHelper::getInstaller();
        $params = [
            'hash'   => $wa_installer->getHash(),
            'domain' => waRequest::server('HTTP_HOST'),
            'beta_test_products' => 1,
            'locale' => wa()->getLocale(),
        ];
        $url = $wa_installer->getInstallationStaticIDUrl();
        $url .= '?'.http_build_query($params);
        return $url;
    }

    protected function logException(Exception $e)
    {
        $message = join(PHP_EOL, [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);
        waLog::log($message, 'installer/' . get_class($this) . '.log');
    }

    protected function logError($e)
    {
        if (!is_scalar($e)) {
            $e = var_export($e, true);
        }
        waLog::log($e, 'installer/' . get_class($this) . '.log');
    }

}
