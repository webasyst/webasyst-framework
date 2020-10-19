<?php

/**
 * Class waLocaleParseEntityApp
 */
class waLocaleParseEntityApp extends waLocaleParseEntity
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
            self::WEBASYST_DEFAULT_PATTERN,
        ];
    }

    /**
     * @return string
     */
    public function getDomainFunctionPattern()
    {
        return self::WEBASYST_DOMAIN_PATTERN;
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
        return $this->getAppID();
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
        return $this->getAppID();
    }

    /**
     * @param string $subpath
     * @return string
     * @throws waException
     */
    public function generatePath($subpath = null)
    {
        return wa($this->getAppID())->getConfig()->getAppPath($subpath);
    }

    /**
     * @return string
     */
    protected function getAppID()
    {
        return $this->app_id;
    }
}