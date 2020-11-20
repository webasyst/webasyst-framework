<?php

class installerWebasystIDApi extends waWebasystIDApi
{
    protected $cache_ttl = 300;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($options['cache_ttl']) && wa_is_int($options['cache_ttl'])) {
            $this->cache_ttl = $options['cache_ttl'];
        }
    }

    /**
     * Get licenses for current contact
     * @param $contact_id
     * @param array $options
     *      - bool $options['from_cache'] [optional] - default is TRUE
     *
     *      - array $options['params'] [optional] - params for API call
     *      -   int|int[] $options['params']['id'] [optional] - ID(s) of concrete license(s)
     * @return array
     * @throws waException
     */
    public function getLicenses($contact_id, array $options = [])
    {
        $from_cache = true;
        if (array_key_exists('from_cache', $options)) {
            $from_cache = $options['from_cache'];
            unset($options['from_cache']);
        }

        $params = isset($options['params']) && is_array($options['params']) ? $options['params'] : [];

        if ($from_cache) {
            $result = $this->getCacheValue(['licenses', $contact_id, $params]);
            if ($result) {
                return $result;
            }
        }

        $result = $this->requestLicenses($contact_id, $params);
        if ($result['status']) {
            $this->setCacheValue(['licenses', $contact_id, $params], $result, $this->cache_ttl);
        }

        return $result;
    }

    /**
     * Invalidate cache for some method
     * @param string $method
     *      - 'licenses': for invalidate getLicenses($contact_id)
     * @param array $arguments
     *      IF $method == 'licenses' THEN $arguments == [$contact_id]
     * @throws waException
     */
    public function clearCache($method, array $arguments)
    {
        $key = $arguments;
        array_unshift($key, $method);
        $this->setCacheValue($key, null, 0);
    }

    /**
     * @param $contact_id
     * @param array $params
     * @return array
     * @throws waDbException
     * @throws waException
     */
    protected function requestLicenses($contact_id, array $params = [])
    {
        $contact = $this->getExistingContact($contact_id);
        if (!$contact) {
            $this->logError([
                'method' => __METHOD__,
                'error' => sprintf("Contact %s not exist", $contact_id)
            ]);
            return [
                'status' => false,
                'details' => [
                    'error' => ''
                ]
            ];
        }

        $token_params = $contact->getWebasystTokenParams();
        if (!$token_params) {
            $this->logError([
                'method' => __METHOD__,
                'error' => sprintf("Contact %s is not authorize (not bound with webasyst ID contact)", $contact_id)
            ]);
            return [
                'status' => false,
                'details' => [
                    'error' => _w('Not connected with a Webasyst ID contact.')
                ]
            ];
        }

        $params['verbose'] = 1;

        $result = $this->tryToGetLicenses($contact, $params);

        // can't refresh token
        if ($result === null) {
            return [
                'status' => false,
                'details' => [
                    'error' => ''
                ]
            ];
        }

        if ($result['status'] == 200) {
            return [
                'status' => true,
                'details' => [
                    'licenses' => $result['response']
                ]
            ];
        }

        if ($result['status'] == 401 && $result['response']['error'] === 'invalid_token') {
            $result = $this->tryToGetLicenses($contact, $params, true);

            // can't refresh token
            if ($result === null) {
                return [
                    'status' => false,
                    'details' => [
                        'error' => ''
                    ]
                ];
            }

            if ($result['status'] == 200) {
                return [
                    'status' => true,
                    'details' => [
                        'licenses' => $result['response']
                    ]
                ];
            }

        }

        return [
            'status' => false,
            'details' => [
                'error' => $result['response']['error']
            ]
        ];
    }

    /**
     * @param $contact_id
     * @param array $params
     *      string $params['token'] - store token
     *      string $params['hash'] - installation hash
     *      string $params['domain'] - domain where to bind license
     *      int $params['license_id'] - ID of license
     * @return array
     */
    public function bindBindLicense($contact_id, array $params = [])
    {
        $contact = $this->getExistingContact($contact_id);
        if (!$contact) {
            $this->logError([
                'method' => __METHOD__,
                'error' => sprintf("Contact %s not exist", $contact_id)
            ]);
            return [
                'status' => false,
                'details' => [
                    'error' => ''
                ]
            ];
        }

        $token_params = $contact->getWebasystTokenParams();
        if (!$token_params) {
            $this->logError([
                'method' => __METHOD__,
                'error' => sprintf("Contact %s is not authorize (not bound with webasyst ID contact)", $contact_id)
            ]);
            return [
                'status' => false,
                'details' => [
                    'error' => _w('Not connected with a Webasyst ID contact.')
                ]
            ];
        }

        $result = $this->tryToBindLicense($contact, $params);

        // can't refresh token
        if ($result === null) {
            return [
                'status' => false,
                'details' => [
                    'error' => ''
                ]
            ];
        }

        if ($result['status'] == 200 || $result['status'] == 204) {
            return [
                'status' => true,
                'details' => [
                    'licenses' => $result['response']
                ]
            ];
        }

        if ($result['status'] == 401 && $result['response']['error'] === 'invalid_token') {
            $result = $this->tryToBindLicense($contact, $params, true);

            // can't refresh token
            if ($result === null) {
                return [
                    'status' => false,
                    'details' => [
                        'error' => ''
                    ]
                ];
            }

            if ($result['status'] == 200) {
                return [
                    'status' => true,
                    'details' => [
                        'licenses' => $result['response']
                    ]
                ];
            }

        }

        return [
            'status' => false,
            'details' => [
                'error' => $result['response']['error']
            ]
        ];
    }

    protected function tryToGetLicenses(waContact $contact, array $params = [], $force_refresh = false)
    {
        $token_params = $contact->getWebasystTokenParams();

        if ($force_refresh) {
            $ok = $this->refreshedTokenParams($token_params, $contact->getId());
        } else {
            $ok = $this->refreshTokenWhenExpired($token_params, $contact->getId());
        }

        if (!$ok) {
            return null;
        }

        return $this->requestApiMethod('licenses', $token_params['access_token'], $params);
    }

    protected function tryToBindLicense(waContact $contact, array $params = [], $force_refresh = false)
    {
        $token_params = $contact->getWebasystTokenParams();

        if ($force_refresh) {
            $ok = $this->refreshedTokenParams($token_params, $contact->getId());
        } else {
            $ok = $this->refreshTokenWhenExpired($token_params, $contact->getId());
        }

        if (!$ok) {
            return null;
        }

        return $this->requestApiMethod('licenses/bind', $token_params['access_token'], $params, waNet::METHOD_POST, [
            'request_format' => waNet::FORMAT_JSON
        ]);
    }

    /**
     * Set cache value
     * @param mixed $key
     * @param mixed $value - value could be any type, but if value is NULL we delete this key association
     * @param int $ttl
     * @throws waException
     */
    protected function setCacheValue($key, $value, $ttl)
    {
        if (!wa_is_int($ttl) || $ttl <= 0) {
            $ttl = 0;
        }

        $key = $this->buildCacheKey($key);

        $cache = wa()->getCache();
        if ($cache) {
            if ($value === null) {
                $cache->delete($key);
            } else {
                $cache->set($key, $value, strtotime(sprintf("add %d seconds", $ttl)));
            }
            return;
        }

        // fallback to file cache
        $cache = new waVarExportCache($key, $ttl);

        if ($value === null) {
            $cache->delete();
            return;
        }

        $value = [
            'value' => $value,
            'time' => time(),
            'ttl' => $ttl
        ];
        $cache->set($value);
    }

    /**
     * Get cache value
     * @param $key
     * @return mixed|null
     * @throws waException
     */
    protected function getCacheValue($key)
    {
        $key = $this->buildCacheKey($key);

        $cache = wa()->getCache();
        if ($cache) {
            return $cache->get($key);
        }

        // fallback to file cache
        $cache = new waVarExportCache($key);

        $value = $cache->get();
        if (!is_array($value) || !isset($value['value']) || !isset($value['ttl']) || !isset($value['time'])) {
            return null;
        }

        $elapsed = time() - $value['time'];
        if ($elapsed > $value['ttl']) {
            $cache->delete();
            return null;
        }

        return $value['value'];
    }

    /**
     * @param array|string $key
     * @return string
     */
    private function buildCacheKey($key)
    {
        if (is_array($key)) {
            foreach ($key as $k => &$v) {
                // to ensure independence on order of keys (or values)
                if (is_array($v)) {
                    if ($this->isAssoc($v)) {
                        ksort($v);
                    } else {
                        sort($v);
                    }
                }
            }
            unset($v);

            $key = var_export($key, true);
        }

        return __CLASS__ . '/' . md5($key);
    }

    private function isAssoc(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
