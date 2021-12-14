<?php

/**
 * Class waLocaleParseEntityWidgets
 */
class waLocaleParseEntityWidgets extends waLocaleParseEntity
{
    /**
     * @var string
     */
    protected $app_id = null;

    /**
     * @var string
     */
    protected $widget_id = null;

    /**
     * waLocaleParseEntityWidgets constructor.
     * @param $params
     * @throws waException
     */
    public function __construct($params)
    {
        if (empty($params['app'])) {
            throw new waException('Invalid app');
        }

        if (empty($params['entity_id'])) {
            throw new waException('Invalid widget id');
        }

        $this->app_id = $params['app'];
        $this->widget_id = $params['entity_id'];
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
        return $this->getAppID().'/widgets/'.$this->getWidgetID();
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
        return $this->getAppID().'_widget_'.$this->getWidgetID();
    }

    /**
     * @param string $subpath
     * @return string
     * @throws waException
     */
    public function generatePath($subpath = null)
    {
        $root = wa($this->getAppID())->getConfig()->getWidgetPath($this->getWidgetID());
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
    protected function getWidgetID()
    {
        return $this->widget_id;
    }
}