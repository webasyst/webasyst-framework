<?php

class webasystSettingsTemplateSMSAction extends webasystSettingsTemplateAction
{
    public function execute()
    {
        $channels = $this->getVerificationChannelModel()->getByType(waVerificationChannelModel::TYPE_SMS);

        $request_id = $this->getRequestId();
        if (!$request_id && !empty($channels)) {
            $redirect_url = $this->getConfig()->getBackendUrl(true).$this->getAppId().'/settings/sms/template/'.key($channels).'/';
            $this->redirect($redirect_url);
        }

        if (!$this->channel instanceof waVerificationChannelSMS) {
            $this->channel = new waVerificationChannelNull();
        }

        $default_templates = $this->channel->getDefaultTemplates();

        $this->view->assign(array(
            'channel'           => $this->channel,
            'default_templates' => $default_templates,
            'channels'          => $channels,
            'numbers'           => $this->getNumbers(),
            'user'              => wa()->getUser(),
            'sidebar_html'      => $this->getSidebarHtml()
        ));
    }

    /**
     * @return string
     */
    protected function getSidebarHtml()
    {
        $vars = $this->view->getVars();
        $this->view->clearAllAssign();
        $sidebar = new webasystSettingsTemplateSMSSidebarAction();
        $html = $sidebar->display();
        $this->view->clearAllAssign();
        $this->view->assign($vars);
        return $html;
    }

    protected function getNumbers()
    {
        $sms_config = wa()->getConfig()->getConfigFile('sms', array());
        $sms_adapters = $this->getSMSAdapters();
        $sms_numbers = array();
        foreach ($sms_config as $number => $params) {
            if (isset($params['adapter']) && isset($sms_adapters[$params['adapter']])) {
                /**
                 * @var waSMSAdapter $sms_adapter
                 */
                $sms_adapter = $sms_adapters[$params['adapter']];
                $adapter_info = $sms_adapter->getInfo();
                $sms_numbers[$number] = ifset($adapter_info['name'], $sms_adapter->getId());
            }
        }

        return $sms_numbers;
    }

    protected function getSMSAdapters()
    {
        $path = $this->getConfig()->getPath('plugins').'/sms/';
        if (!file_exists($path)) {
            return array();
        }
        $dh = opendir($path);
        $adapters = array();
        while (($f = readdir($dh)) !== false) {
            if ($f === '.' || $f === '..' || !is_dir($path.$f)) {
                continue;
            } elseif (file_exists($path.$f.'/lib/'.$f.'SMS.class.php')) {
                require_once($path.$f.'/lib/'.$f.'SMS.class.php');
                $class_name = $f.'SMS';
                $adapters[$f] = new $class_name(array());
            }
        }
        closedir($dh);

        if (class_exists('wadebugSMS')) {
            $adapters['wadebug'] = new wadebugSMS();
        }

        return $adapters;
    }
}
