<?php

/**
 * Provider healthy urls for webasyst ID services
 * If url not working you can complain about it and after some max tries urls will be marked as not healthy and provider suggests alternative urls
 */
class waWebasystIDUrlsProvider
{
    protected $ttl;

    /**
     * Currently selected endpoints
     * @var array
     */
    protected $selected = [];

    /**
     * @var waWebasystIDEndpointsHealthChecker
     */
    protected $health_checker;

    /**
     * @var waWebasystIDConfig
     */
    protected $config;

    public function __construct(array $options = [])
    {
        if (isset($options['ttl']) && wa_is_int($options['ttl'])) {
            $this->ttl = intval($options['ttl']);
        } else {
            $this->ttl = waSystemConfig::isDebug() ? 300 : 3600;
        }

        if (isset($options['config']) && $options['config'] instanceof waWebasystIDConfig) {
            $this->config = $options['config'];
        } else {
            $this->config = new waWebasystIDConfig();
        }

        if (isset($options['health_checker']) && $options['health_checker'] instanceof waWebasystIDEndpointsHealthChecker) {
            $this->health_checker = $options['health_checker'];
        } else {
            $this->health_checker = new waWebasystIDEndpointsHealthChecker([
                'ttl' => $this->ttl
            ]);
        }
    }

    /**
     * In case when need ensure guaranteed healthy auth endpoint
     * Be careful caching is ignoring here
     * @return bool
     */
    public function ensureHealthyAuthEndpoint()
    {
        $endpoints = $this->getEndpointsOfType('oauth2');
        foreach ($endpoints as $endpoint) {
            if ($this->health_checker->isEndpointHealthy($endpoint, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $controller_url [optional] - controller url of auth center
     * @param array $params [optional] - get params for url
     * @return string
     */
    public function getAuthCenterUrl($controller_url = null, $params = [])
    {
        $url = $this->getAuthEndpoint();

        $auth_center_url = rtrim($url, '/') . '';
        if ($controller_url) {
            $auth_center_url .= '/' . $controller_url;
        }
        if ($params) {
            $auth_center_url .= '?' . http_build_query($params);
        }
        return $auth_center_url;
    }

    /**
     * @param string $controller_url - api method (controller)
     * @param array $params - get parameters of api method
     *      - string $params['version'] [optional] - Additional parameter - version of API, default is v1
     * @return string
     */
    public function getApiUrl($controller_url, $params = [])
    {
        $url = $this->getApiEndpoint();
        $api_url = rtrim($url, '/');

        $api_v = 1;
        if ($params && isset($params['version'])) {
            $api_v = $params['version'];
            unset($params['version']);
        }

        // default is /v1/
        $api_url .= '/v' . $api_v . '/';

        // api controller itself
        $api_url .= trim($controller_url, '/');

        if ($params) {
            $api_url .= '?' . http_build_query($params);
        }

        return $api_url;
    }

    public function complainAboutAuthEndpoint()
    {
        $current_endpoint = $this->getAuthEndpoint();
        if (!$this->health_checker->isEndpointHealthy($current_endpoint)) {
            $this->updateComplainCount($current_endpoint, 0);
            return;
        }

        $count = $this->getComplainCount($current_endpoint);
        $count++;

        if ($count >= $this->config->getEndpointMaxTries()) {
            $this->setAuthEndpointHealthyStatus(false);
            $this->resetComplainCount($current_endpoint);
        } else {
            $this->updateComplainCount($current_endpoint, $count);
        }
    }

    public function complainAboutApiEndpoint()
    {
        $current_endpoint = $this->getApiEndpoint();
        if (!$this->health_checker->isEndpointHealthy($current_endpoint)) {
            $this->updateComplainCount($current_endpoint, 0);
            return;
        }

        $count = $this->getComplainCount($current_endpoint);
        $count++;

        if ($count >= $this->config->getEndpointMaxTries()) {
            $this->setApiEndpointHealthyStatus(false);
            $this->resetComplainCount($current_endpoint);
        } else {
            $this->updateComplainCount($current_endpoint, $count);
        }
    }

    public function setApiEndpointHealthyStatus($healthy)
    {
        $endpoint = $this->getApiEndpoint();
        $this->health_checker->setEndpointHealthyStatus($endpoint, boolval($healthy));
        unset($this->selected['api']);
    }

    public function setAuthEndpointHealthyStatus($healthy)
    {
        $endpoint = $this->getAuthEndpoint();
        $this->health_checker->setEndpointHealthyStatus($endpoint, boolval($healthy));
        unset($this->selected['oauth2']);
    }

    public function resetCache()
    {
        $cache = $this->getCache();
        $cache->delete();
    }

    protected function getCache()
    {
        return new waWebasystIDCache(md5(__METHOD__), $this->ttl);
    }

    protected function getCacheData()
    {
        $cache = $this->getCache();
        $data = $cache->get();
        return is_array($data) ? $data : [];
    }

    protected function updateComplainCount($endpoint, $count)
    {
        $data = $this->getCacheData();
        $updated_data = $data;

        if ($count >= 0) {
            $updated_data[$endpoint] = $count;
        } else {
            unset($updated_data[$endpoint]);
        }

        if (!$updated_data) {
            $this->getCache()->delete();
        } elseif ($updated_data != $data) {
            $this->getCache()->set($updated_data);
        }
    }

    /**
     * Number of complaints so far to this endpoint
     * @param string $endpoint
     * @return int
     */
    protected function getComplainCount($endpoint)
    {
        $data = $this->getCacheData();
        $count = isset($data[$endpoint]) ? $data[$endpoint] : 0;
        return wa_is_int($count) ? intval($count) : 0;
    }

    protected function resetComplainCount($endpoint)
    {
        $data = $this->getCacheData();
        $updated_data = $data;
        unset($updated_data[$endpoint]);

        if (!$updated_data) {
            $this->getCache()->delete();
        } elseif ($updated_data != $data) {
            $this->getCache()->set($updated_data);
        }
    }

    /**
     * @return string
     */
    protected function getAuthEndpoint()
    {
        if (empty($this->selected['oauth2'])) {
            $this->selected['oauth2'] = $this->getFirstHealthEndpoint($this->getEndpointsOfType('oauth2'));
        }
        return $this->selected['oauth2'];
    }

    /**
     * @return string
     */
    protected function getApiEndpoint()
    {
        if (empty($this->selected['api'])) {
            $this->selected['api'] = $this->getFirstHealthEndpoint($this->getEndpointsOfType('api'));
        }
        return $this->selected['api'];
    }

    protected function getEndpointsOfType($type)
    {
        $endpoints = [];
        foreach ($this->config->getEndpoints() as $endpoint) {
            if (!empty($endpoint[$type])) {
                $endpoints[] = $endpoint[$type];
            }
        }
        return $endpoints;
    }

    /**
     * @param string[] $endpoints
     * @return string
     */
    protected function getFirstHealthEndpoint(array $endpoints)
    {
        if (!$endpoints) {
            return '';
        }

        foreach ($endpoints as $endpoint) {
            if ($this->health_checker->isEndpointHealthy($endpoint)) {
                return $endpoint;
            }
        }

        return reset($endpoints);
    }
}
