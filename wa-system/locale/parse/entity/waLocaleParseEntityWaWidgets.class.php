<?php

/**
 * Class waLocaleParseEntityWaWidgets
 */
class waLocaleParseEntityWaWidgets extends waLocaleParseEntity
{
    /**
     * @var string
     */
    protected $widget_id = null;

    /**
     * waLocaleParseEntityWaWidgets constructor.
     * @param $params
     * @throws waException
     */
    public function __construct($params)
    {
        if (empty($params['entity_id'])) {
            throw new waException('Invalid widget id');
        }

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
            self::WEBASYST_DEFAULT_PATTERN,
        ];
    }

    /**
     * @return array
     * @throws waException
     */
    public function getSources()
    {
        $result = [
            $this->getWidgetPath(),
        ];

        return $result;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return 'wa-widgets/'.$this->getPluginID();
    }

    /**
     * @return string
     * @throws waException
     */
    public function getLocalePath()
    {
        return $this->getWidgetPath().'/locale';
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return 'widget'.'_'.$this->getPluginID();
    }

    /**
     * @return string
     * @throws waException
     */
    public function getWidgetPath()
    {
        return wa()->getConfig()->getPath('widgets').'/'.$this->getPluginID();
    }

    /**
     * @return string
     */
    protected function getPluginID()
    {
        return $this->widget_id;
    }
}