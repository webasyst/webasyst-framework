<?php

class installerBackendStoreLayout extends waLayout
{
    /**
     * @var waFunctionCache
     */
    protected static $function_cache;

    public function execute()
    {
        $messages = $this->getMessages();

        $this->view->assign(array(
            'messages'            => $messages,
            'update_counter'      => $this->getUpdateCounter(),
            'module'              => waRequest::param('module', 'backend'),
            'filters'             => installerStoreHelper::getFilters(),
            'store_sidebar_items' => $this->getStoreSidebarItems(),
            'store_sidebar_type'  => installerStoreHelper::getSidebarType(),
            'store_path'          => installerStoreHelper::getStorePath(),
            'sidebar_only'        => $this->isSidebarOnly(),
        ));
    }

    protected function getMessages()
    {
        $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));
        installerHelper::checkUpdates($messages);
        if ($m = $this->view->getVars('messages')) {
            $messages = array_merge($m, $messages);
        }

        return $messages;
    }

    protected function getUpdateCounter()
    {
        $model = new waAppSettingsModel();
        return $model->get($this->getApp(), 'update_counter');
    }

    protected function getStoreSidebarItems()
    {
        try {
            $init_data = $this->getInstallerConfig()->getInitData();

            if (empty($init_data['sidebar']) || !is_array($init_data['sidebar'])) {
                throw new waException('Failed to load sidebar data');
            }

            return $init_data['sidebar'];
        } catch (Exception $e) {
            return false;
        }
    }

    protected function isSidebarOnly()
    {
        return waRequest::get('reload_sidebar', false);
    }

    /**
     * @return installerConfig
     */
    protected function getInstallerConfig()
    {
        return wa('installer')->getConfig();
    }
}