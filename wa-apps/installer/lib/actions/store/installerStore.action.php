<?php

class installerStoreAction extends waViewAction
{
    protected $store_path;

    protected $params = array();

    /**
     * @param null|array $params
     */
    public function __construct($params = null)
    {
        parent::__construct($params);

        if (!empty($params['store_path']) && is_string($params['store_path'])) {
            $this->store_path = $params['store_path'];
            unset($params['store_path']);
        }

        if (!empty($params) && is_array($params)) {
            $this->params = $params;
        }
    }

    public function execute()
    {
        $this->setLayout(new installerBackendStoreLayout());
        $messages = array();
        try {
            $store_token = $this->getInstallerConfig()->getTokenData();
        } catch (Exception $ex) {
            // Save the error in the log and add to the common array
            installerHelper::handleException($ex, $messages);
        }

        // If we get the messages in action - override the messages from the layout?
        if ($messages) {
            $this->getLayout()->assign('messages', $messages);
        }

        $in_app = (bool) ifempty($this->params, 'in_app', false);
        $return_url = ifempty($this->params, 'return_url', null);

        $user_locale = wa()->getLocale();
        if ($user_locale != 'ru_RU') {
            $user_locale = 'en_US';
        }

        $this->view->assign(array(
            'filters'        => installerStoreHelper::getFilters(),
            'path_to_module' => wa()->getAppUrl(null, true).'store/',
            'store_url'      => installerStoreHelper::getStoreUrl(),
            'store_path'     => $this->getStorePath(),
            'store_token'    => ifempty($store_token),
            'installer_url'  => wa()->getUrl(true),
            'in_app'         => $in_app,
            'return_url'     => $return_url,
            'user_locale'    => $user_locale,
            'csrf'           => waRequest::cookie('_csrf', ''),
        ));
    }

    /**
     * @return installerConfig
     */
    protected function getInstallerConfig()
    {
        return wa('installer')->getConfig();
    }

    protected function getStorePath()
    {
        return $this->store_path ? $this->store_path : installerStoreHelper::getStorePath();
    }
}