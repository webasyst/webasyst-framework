<?php

/**
 * Class waLocaleParseEntityWebasyst
 */
class waLocaleParseEntityWebasyst extends waLocaleParseEntity
{
    /**
     * @var string
     */
    protected $app_id = null;

    /**
     * waLocaleParseEntityApp constructor.
     * @param $params
     * @throws waException
     */
    public function __construct($params)
    {
        if (empty($params['app'])) {
            throw new waException('Invalid app');
        }

        $this->app_id = $params['app'];
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
            self::WEBASYST_SYSTEM_PATTERN,
        ];
    }

    /**
     * @return array
     * @throws waException
     */
    public function getSources()
    {
        $result = [
            $this->generatePath(),
        ];

        return $result;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->getAppID();
    }

    /**
     * @return string
     * @throws waException
     */
    public function getLocalePath()
    {
        return wa($this->getAppID())->getConfig()->getAppPath('locale');
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->getAppID();
    }

    public function getBacktickPattern()
    {
        // find system form
        // example [s``]
        $pattern = "/\\[s?";

        // Lets you escape the back quote. `i\`m text`
        // Break the regular expression into pieces so that it does not find itself o_0
        $pattern .= "`((?:\\\\`|[^`])+?)";
        $pattern .= "`\\]/usi";

        return $pattern;
    }

    /**
     * @return string
     * @throws waException
     */
    public function generatePath()
    {
        return $this->getRootPath().'/wa-system';
    }

    /**
     * @return string
     */
    protected function getAppID()
    {
        return $this->app_id;
    }
}