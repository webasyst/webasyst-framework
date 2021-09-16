<?php

class installerSettingsDisconnectBetaTestProductController extends waJsonController
{
    public function execute()
    {
        $ok = $this->doQuery($this->getProductId());
        if (!$ok) {
            $this->errors = [
                'error' => 'cant_disconnect',
                'description' => _w("Can’t disconnect.")
            ];
        }
    }

    protected function doQuery($product_id)
    {
        $options = [
            'timeout' => 30,
            'request_format' => waNet::FORMAT_RAW,
            'format' => waNet::FORMAT_JSON
        ];

        $params = [
            'product_id' => $product_id
        ];

        $net = new waNet($options);

        $response = null;
        try {
            $response = $net->query($this->getUrl(), $params, waNet::METHOD_POST);
        } catch (Exception $e) {
            $this->logException($e);
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return false;
        }

        if (!$response) {
            $this->logError('empty response');
            return false;
        }

        if (empty($response['status']) || $response['status'] !== 'ok') {
            $this->logError('fail response');
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    protected function getProductId()
    {
        return $this->getRequest()->post('product_id', 0, waRequest::TYPE_INT);
    }

    protected function getUrl()
    {
        $wa_installer = installerHelper::getInstaller();
        $params = [
            'hash'   => $wa_installer->getHash(),
            'domain' => waRequest::server('HTTP_HOST'),
        ];
        $url = $wa_installer->getInstallationBetaTestProductDisconnectUrl();
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
