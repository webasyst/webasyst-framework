<?php

class installerSettingsStaticIDController extends waJsonController
{
    public function execute()
    {
        $this->response['id'] = $this->getStaticID();
    }

    protected function getStaticID()
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

        if (!$response || empty($response['data']['id'])) {
            return null;
        }

        return $response['data']['id'];
    }

    protected function getUrl()
    {
        $wa_installer = installerHelper::getInstaller();
        $init_url_params = array(
            'hash'   => $wa_installer->getHash(),
            'domain' => waRequest::server('HTTP_HOST'),
        );
        $url = $wa_installer->getInstallationStaticIDUrl();
        $url .= '?'.http_build_query($init_url_params);
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
