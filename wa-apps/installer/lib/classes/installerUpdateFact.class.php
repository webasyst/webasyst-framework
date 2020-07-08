<?php

/**
 * Informs the remote update server about changes to the installation package
 */
class installerUpdateFact
{
    const ACTION_ADD = 'add';
    const ACTION_DEL = 'del';

    protected $action;
    protected $fact_url;
    protected $token;
    protected $products;

    /**
     * @param string $action - add/del
     * @param array $products - an array of updated products (installed, updated, removed)
     */
    public function __construct($action, array $products)
    {
        $this->action = $action;
        $this->products = array_unique($products);

        try {
            $wa_installer = installerHelper::getInstaller();
            $this->fact_url = $wa_installer->getInstallerFactUrl();
        } catch (Exception $e) { }
    }

    public function query($repeated_request = false)
    {
        try {
            $url = $this->buildRequestUrl();
            if ($url) {
                $res = $this->getNet()->query($url);
            }
        } catch (waException $e) {}

        if (!$repeated_request && ifempty($res, 'status', 'fail') != 'ok') {
            $this->loadToken(true);
            $this->query(true);
        }
    }

    protected function loadToken($actual = false)
    {
        if ($actual || !$this->token) {
            $config = installerStoreHelper::getInstallerConfig();

            try {
                $token = $config->getTokenData($actual);
            } catch (Exception $e) {
                return;
            }

            $token['expire_datetime'] = $token['remote_expire_datetime'];
            unset($token['remote_expire_datetime']);

            $this->token = $token;
        }
    }

    protected function buildRequestUrl()
    {
        if (!$this->token) {
            $this->loadToken();
        }

        if (!$this->token) {
            return null;
        }

        $params = array(
            'token'    => join(';', $this->token),
            'products' => $this->products,
        );

        return $this->fact_url . $this->action . '/?'.http_build_query($params);
    }

    /**
     * @return waNet
     */
    protected function getNet()
    {
        static $net;

        if (!$net) {
            $net_options = array(
                'timeout' => 7,
                'format'  => waNet::FORMAT_JSON,
            );
            $net = new waNet($net_options);
        }

        return $net;
    }
}