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
}
