<?php

/**
 * Class boxberryShippingHandbookManager
 */
abstract class boxberryShippingHandbookManager
{
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
     * boxberryShippingHandbookManager constructor.
     * @param boxberryShippingApiManager $api_manager
     * @param array $data
     */
    public function __construct(boxberryShippingApiManager $api_manager, $data = [])
    {
        $this->api_manager = $api_manager;
        $this->data = $data;
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
            $cache = new waVarExportCache($data['key'], $data['ttl'], 'webasyst/shipping/boxberry');
            $cache->set($data['value']);
        }
    }

    /**
     * @param string $key
     * @return array|null
     */
    protected function getFromCache($key)
    {
        $cache = new waVarExportCache($key, -1, 'webasyst/shipping/boxberry');
        $result = $cache->get();
        return $result;
    }
}