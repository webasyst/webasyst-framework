<?php

/**
 * Class waLocaleParseEntityPlugins
 */
class waLocaleParseEntityPlugins extends waLocaleParseEntity
{
    /**
     * @var string
     */
    protected $app_id = null;

    /**
     * @var string
     */
    protected $plugin_id = null;

    /**
     * waLocaleParseEntityPlugins constructor.
     * @param $params
     * @throws waException
     */
    public function __construct($params)
    {
        if (empty($params['app'])) {
            throw new waException('Invalid app');
        }

        if (empty($params['entity_id'])) {
            throw new waException('Invalid plugin id');
        }

        $this->app_id = $params['app'];
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
            self::WEBASYST_PLUGIN_PATTERN,
        ];
    }

    /**
     * @return array
     * @throws waException
     */
    public function getSources()
    {
        $result = [
            $this->generatePath('templates'),
            $this->generatePath('js'),
            $this->generatePath('lib'),
        ];

        return $result;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->getAppID().'/plugins/'.$this->getPluginID();
    }

    /**
     * @return string
     * @throws waException
     */
    public function getLocalePath()
    {
        return $this->generatePath('locale');
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->getAppID().'_'.$this->getPluginID();
    }

    /**
     * @param string $subpath
     * @return string
     * @throws waException
     */
    public function generatePath($subpath = null)
    {
        $root = wa($this->getAppID())->getConfig()->getPluginPath($this->getPluginID());
        if ($subpath) {
            $root .='/'.$subpath;
        }

        return $root;
    }

    /**
     * @return string
     */
    protected function getAppID()
    {
        return $this->app_id;
    }

    /**
     * @return string
     */
    protected function getPluginID()
    {
        return $this->plugin_id;
    }
}