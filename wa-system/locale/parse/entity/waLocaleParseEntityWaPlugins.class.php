<?php

/**
 * Class waLocaleParseEntityWaPlugins
 */
class waLocaleParseEntityWaPlugins extends waLocaleParseEntity
{
    /**
     * @var string
     */
    protected $plugin_id = null;

    /**
     * @var string
     */
    protected $plugin_type = null;

    /**
     * waLocaleParseEntityWaPlugins constructor.
     * @param $params
     * @throws waException
     */
    public function __construct($params)
    {
        if (empty($params['app'])) {
            throw new waException('Invalid plugin type');
        }

        if (empty($params['entity_id'])) {
            throw new waException('Invalid plugin_id');
        }

        $this->plugin_type = $params['app'];
        $this->plugin_id = $params['entity_id'];
    }

    /**
     * @return array
     */
    public function getOpenFunctionPatterns()
    {
        return [
            self::OPEN_SPRINTF_PATTERN
        ];
    }

    /**
     * @return array
     */
    public function getWebasystFunctionPatterns()
    {
        return [
            self::WEBASYST_DEFAULT_PATTERN,
            self::WEBASYST_SYSTEM_PLUGIN_PATTERN,
        ];
    }

    /**
     * @return array
     * @throws waException
     */
    public function getSources()
    {
        $result = [
            $this->getPluginPath(),
        ];

        return $result;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return 'wa-plugins/'.$this->getPluginType().'/'.$this->getPluginID();
    }

    /**
     * @return string
     * @throws waException
     */
    public function getLocalePath()
    {
        return $this->getPluginPath().'/locale';
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->getPluginType().'_'.$this->getPluginID();
    }

    /**
     * @return string
     * @throws waException
     */
    public function getPluginPath()
    {
        $wa_plugins = wa()->getConfig()->getPath('plugins').'/';
        $wa_plugins .= $this->getPluginType().'/'.$this->getPluginID();

        return $wa_plugins;
    }

    /**
     * @return string
     */
    protected function getPluginType()
    {
        return $this->plugin_type;
    }

    /**
     * @return string
     */
    protected function getPluginID()
    {
        return $this->plugin_id;
    }
}