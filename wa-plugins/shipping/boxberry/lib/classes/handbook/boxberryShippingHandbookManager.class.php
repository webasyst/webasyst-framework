<?php

/**
 * Class boxberryShippingHandbookManager
 */
abstract class boxberryShippingHandbookManager
{
    /**
     * The folder in which data is stored
     */
    const CACHE_PATH = 'webasyst/shipping/boxberry';

    /**
     * The method should implement receiving data from the Boxberry server and writing it to the cache
     *
     * @return array
     */
    abstract protected function getFromAPI();

    /**
     * The method should return the key by which the handbook is in the cache
     *
     * @return string
     */
    abstract protected function getCacheKey();

    /**
     * @var boxberryShippingApiManager
     */
    protected $api_manager = null;

    /**
     * Additional information needed in handbooks
     * @var array
     */
    protected $data = [];

    /**
     * @var boxberryShipping
     */
    protected $bxb = null;

    /**
     * boxberryShippingHandbookManager constructor.
     * @param boxberryShippingApiManager $api_manager
     * @param array $data
     * @param boxberryShipping $bxb
     */
    public function __construct(boxberryShippingApiManager $api_manager, $data = [], boxberryShipping $bxb = null)
    {
        $this->api_manager = $api_manager;
        $this->data = $data;
        $this->bxb = $bxb;
    }

    /**
     * Returns a handbook from the cache. If not in the cache, it tries to get the api reference
     *
     * @return array
     */
    public function getHandbook()
    {
        $handbook = $this->getFromCache($this->getCacheKey());
        if (!$handbook) {
            $handbook = $this->getFromAPI();
        }
        return $handbook;
    }

    /**
     * @param $data
     */
    protected function setToCache($data)
    {
        if (!empty($data['key']) && !empty($data['ttl']) && !empty($data['value'])) {
            $cache = new waVarExportCache($data['key'], $data['ttl'], self::CACHE_PATH);
            $cache->set($data['value']);
        }
    }

    /**
     * @param string $key
     * @return array|null
     */
    protected function getFromCache($key)
    {
        $cache = new waVarExportCache($key, -1, self::CACHE_PATH);
        $result = $cache->get();
        return $result;
    }

    /**
     * @param $data
     * @param null $method
     */
    protected function log($data, $method = null)
    {
        $info = $data;
        if (is_array($data)) {
            $info = var_export($data, true);
        }

        $api_method = 'Not specified';
        if ($method) {
            $api_method = $method;
        }


        $log = <<<HTML
_____________________       
API METHOD: {$api_method}
INFO: {$info}
_____________________
HTML;

        waLog::log($log, 'wa-plugins/shipping/boxberry/handbook_info.log');

    }
}
