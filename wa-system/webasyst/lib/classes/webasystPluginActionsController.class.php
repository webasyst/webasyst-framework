<?php

class webasystPluginActionsController extends waController
{
    /** @var waSystemPlugin */
    protected $plugin;
    /** @var string */
    protected $app_id;

    public function preExecute()
    {
        parent::preExecute();
        $this->app_id = waRequest::get('app_id', 'webasyst', waRequest::TYPE_STRING);
    }

    protected function getPluginRights($type)
    {
        $user = wa()->getUser();
        if ($this->app_id != 'webasyst') {
            $info = wa()->getAppInfo($this->app_id);
            $field = sprintf('%s_plugins', $type);
            if (empty($info[$field])) {
                throw new waException('Application does not support system plugins');
            }

            $params = $info[$field];
            if (is_array($params) && !empty($params['rights'])) {
                $rights = $user->getRights($this->app_id, $params['rights']);
            } else {
                $rights = $user->isAdmin($this->app_id);
            }
        } else {
            $rights = $user->isAdmin($this->app_id);
        }
        return $rights;
    }

    /**
     * @throws waException
     */
    public function execute()
    {
        try {
            $type = constant(get_class($this->plugin).'::PLUGIN_TYPE');
            $rights = $this->getPluginRights($type);
            if (empty($rights)) {
                throw new waRightsException('User has no rights');
            }
            if (empty($this->plugin)) {
                throw new waException('Plugin not found');
            }

            $action = waRequest::param('plugin_action');
            $module = waRequest::param('plugin_module');

            $controller = $this->plugin->getController(ifempty($module, 'backend'), ifempty($action, 'default'));

            $params = waRequest::param('plugin_params');

            $controller->run($params);

        } catch (waException $ex) {
            waLog::log($ex->getMessage());
            throw $ex;
        }
    }
}
