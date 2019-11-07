<?php

abstract class waAppShipping implements waiPluginApp
{
    protected $app_id;

    final public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        if (!$this->app_id) {
            $this->app_id = wa()->getApp();
        }
    }

    /**
     *
     * @return string
     */
    final public function getAppId()
    {
        return $this->app_id;
    }

    /**
     *
     * Callback method handler for plugin
     * @param string $method
     * @throws waException
     * @return mixed
     */
    final public function execCallbackHandler($method)
    {
        $args = func_get_args();
        array_shift($args);
        $method_name = "callback".ucfirst($method)."Handler";
        if (!method_exists($this, $method_name)) {
            throw new waException('Unsupported callback handler method '.$method);
        }
        return call_user_func_array(array($this, $method_name), $args);
    }

    /**
     *
     * Get private data storage path
     * @param int $order_id
     * @param string $path
     * @return string
     */
    public function getDataPath($order_id, $path = null)
    {
        return false;
    }

    /**
     * @param $name string
     * @return mixed
     */
    public function getAppProperties($name = null)
    {
        $info = wa()->getAppInfo($this->app_id);
        $properties = ifset($info['shipping_plugins']);
        if (!is_array($properties)) {
            $properties = array();
        }
        return $name ? ifset($properties[$name]) : $properties;
    }

    /**
     * @return array string[string] array of available units with their names
     */
    public function getAvailableLinearUnits()
    {
        return array(
            'm'  => 'm',
            'ft' => 'ft',
        );
    }

    public function uninstall($plugin_id)
    {
        ;
    }
}
