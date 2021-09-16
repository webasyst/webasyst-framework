<?php

class waWebasystIDEndpointsHealthChecker
{
    private $ttl = 3600;

    public function __construct(array $option = [])
    {
        if (isset($option['ttl']) && wa_is_int($option['ttl']) && $option['ttl'] >= 0) {
            // ttl = 0 is useful for tests
            $this->ttl = intval($option['ttl']);
        }
    }

    /**
     * @param string $endpoint
     * @param bool $force - force check or trying getting from cache first
     * @return bool
     */
    public function isEndpointHealthy($endpoint, $force = false)
    {
        $health_status = null;
        if (!$force) {
            $health_status = $this->getCachedEndpointStatus($endpoint);
        }

        if ($health_status === null) {
            $health_status = $this->healthCheck($endpoint);
            $this->updateCachedEndpointStatus($endpoint, $health_status);
        }

        return boolval($health_status);
    }

    public function setEndpointHealthyStatus($endpoint, $healthy)
    {
        $this->updateCachedEndpointStatus($endpoint, $healthy);
    }

    protected function healthCheck($endpoint)
    {
        $url = rtrim($endpoint, '/') . '/health';
        $result = $this->requestHealthEndpoint($url);
        return $result['status'];
    }

    protected function requestHealthEndpoint($url)
    {
        $options = [
            'timeout' => 20,
            'format' => waNet::FORMAT_JSON
        ];

        $net = new waNet($options);
        $response = null;
        try {
            $response = $net->query($url);
        } catch (Exception $e) {
            $this->logException($e);
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return $this->packFailResult("fail_" . $e->getCode(), $e->getMessage());
        }


        // No response from API
        if (!$response) {
            return $this->packFailResult("unknown", "Unknown error");
        }

        // Error from API
        if (!isset($response['status']) || $response['status'] === 'fail') {
            $errors = isset($response['errors']) && is_array($response['errors']) ? $response['errors'] : [];
            $error_code = "unknown";
            $error_message = "Unknown api error";
            if ($errors) {
                $error_code = key($errors);
                $error_message = $errors[$error_code];
            }
            return $this->packFailResult($error_code, $error_message);
        }

        return $this->packOkResult();
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

    protected function updateCachedEndpointStatus($endpoint, $status)
    {
        $data = $this->getCacheData();
        $updated_data = $data;
        $updated_data[$endpoint] = boolval($status);

        if ($updated_data != $data) {
            $this->getCache()->set($updated_data);
        }
    }

    protected function getCachedEndpointStatus($endpoint)
    {
        $data = $this->getCacheData();
        if (!isset($data[$endpoint])) {
            return null;
        }
        return boolval($data[$endpoint]);
    }

    protected function packFailResult($error_code, $error_message)
    {
        return [
            'status' => false,
            'details' => [
                'error_code' => $error_code,
                'error_message' => $error_message
            ]
        ];
    }

    protected function packOkResult($details = [])
    {
        return [
            'status' => true,
            'details' => $details
        ];
    }

    protected function logException(Exception $e)
    {
        $message = join(PHP_EOL, [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);
        waLog::log($message, 'webasyst/' . get_class($this) . '.log');
    }

    protected function logError($e)
    {
        if (!is_scalar($e)) {
            $e = var_export($e, true);
        }
        waLog::log($e, 'webasyst/' . get_class($this) . '.log');
    }
}
