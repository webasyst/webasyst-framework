<?php

/**
 * Class waWebasystIDAccessTokenManager
 *
 * Webasyst ID token manager
 * Based on JWT standard with HS512 digital sign
 *
 * This class is intended for work with JWT access token
 *
 */
class waWebasystIDAccessTokenManager
{
    /**
     * Generate JWT access token with HS512 digital sign
     *
     * @param array $params
     *  All these keys is required
     *      string      $params['issuer']     - issuer (who release token)
     *      int         $params['contact_id'] - ID of contact for whom release token OR -1 for service-to-service communication token
     *      string      $params['client_id']  - String ID of client for whom release token, contact must be "related" with this client
     *      int         $params['ttl']        - ttl of token in seconds
     *      string[]    $params['scopes']     - list of scopes access to which will be allowed by this access token
     *
     * Other keys is optional
     *      string      $params['email']      - email of contact for whom release token
     *      string      $params['device_id']  - device ID
     *
     * @param string $secret - secret for sign
     * @return string|null - if something wrong returns null
     */
    public function releaseToken(array $params, $secret)
    {
        $header = [
            "typ" => "JWT",
            "alg" => "HS512"
        ];

        if (!isset($params['issuer']) || !is_string($params['issuer'])) {
            return null;
        }

        if (!isset($params['contact_id']) || !wa_is_int($params['contact_id']) || $params['contact_id'] < -1) {
            return null;
        }

        if (!isset($params['client_id']) || !is_string($params['client_id'])) {
            return null;
        }

        if (!isset($params['ttl']) || !wa_is_int($params['ttl']) || $params['ttl'] <= 0) {
            return null;
        }

        $scopes = [];
        if (isset($params['scopes']) && is_array($params['scopes'])) {
            $scopes = $params['scopes'];
            $scopes = waUtils::toStrArray($scopes);
        }
        $scopes = array_unique($scopes);
        sort($scopes);  // ensure that order of array not impact to token

        $now_time = $this->getNowTime();

        $sub = [
            'contact_id' => intval($params['contact_id']),
            'client_id' => $params['client_id']
        ];

        if (!empty($params['device_id'])) {
            $sub['device_id'] = $params['device_id'];
        }

        $payload = [
            'iss' => $params['issuer'],
            'sub' => json_encode($sub),
            'scopes' => json_encode($scopes),
            'iat' => $now_time,
            'exp' => $now_time + intval($params['ttl']),
            'jti' => $this->generateJTI(),

            // extra payload
            'email' => isset($params['email']) && is_string($params['email']) ? $params['email'] : '',
        ];

        if (isset($params['phone']) && is_string($params['phone'])) {
            $payload['phone'] = $params['phone'];
        }

        if (isset($params['two_fa_mode']) && is_string($params['two_fa_mode'])) {
            $payload['two_fa_mode'] = $params['two_fa_mode'];
        }

        if (isset($params['two_fa_time']) && is_string($params['two_fa_time'])) {
            $payload['two_fa_time'] = $params['two_fa_time'];
        }

        if (!empty(ifempty($params['aux_info']))) {
            $payload['aux_info'] = json_encode($params['aux_info']);
        }

        $header_str = json_encode($header);
        $header_str = waUtils::urlSafeBase64Encode($header_str);
        $payload_str = json_encode($payload);
        $payload_str = waUtils::urlSafeBase64Encode($payload_str);

        $body = $header_str . '.' . $payload_str;

        $sign = hash_hmac('sha512', $body, $secret);

        return $body . '.' . waUtils::urlSafeBase64Encode($sign);
    }

    /**
     * Extract some info from token (issuer, contact_id and client_id)
     * @param $token
     * @return array|null $result - returns NULL on failure otherwise
     *      string $result['issuer']        - issuer (who release token), if failure on extract this info then ''
     *      int    $result['contact_id']    - ID of contact for whom release token, if failure on extract this info then 0
     *      string $result['client_id']     - String ID of client for whom release token, contact must be "related" with this client, if failure on extract this info then ''
     *      string $result['scopes']        - scopes of token
     *
     *      string $result['email'] [optional]      - email of contact for whom release token, default is ''
     *      string $result['device_id'] [optional]  - device ID
     */
    public function extractTokenInfo($token)
    {
        $payload = $this->extractPayload($token);
        if (!$payload) {
            return null;
        }

        $info = [
            'issuer' => '',
            'contact_id' => 0,
            'client_id' => '',
            'device_id' => '',
            'email' => '',
            'scopes' => []
        ];

        if (isset($payload['iss']) && is_string($payload['iss'])) {
            $info['issuer'] = $payload['iss'];
        }

        if (isset($payload['sub']) && is_string($payload['sub'])) {
            $sub = json_decode($payload['sub'], true);
            if (!is_array($sub)) {
                $sub = [];
            }
            if (isset($sub['contact_id']) && wa_is_int($sub['contact_id']) && $sub['contact_id'] >= -1) {
                $info['contact_id'] = intval($sub['contact_id']);
            }
            if (isset($sub['client_id']) && is_string($sub['client_id'])) {
                $info['client_id'] = $sub['client_id'];
            }
            if (isset($sub['device_id']) && is_string($sub['device_id'])) {
                $info['device_id'] = $sub['device_id'];
            }
        }

        if (isset($payload['scopes'])) {
            $scopes = json_decode($payload['scopes'], true);
            if (!is_array($scopes)) {
                $scopes = [];
            }
            $info['scopes'] = $scopes;
        }

        if ($sub['contact_id'] > 0) {
            $info['scopes'][] = 'profile';
            $info['scopes'] = array_unique($info['scopes']);
        }

        if (isset($payload['email']) && is_string($payload['email'])) {
            $info['email'] = $payload['email'];
        }

        if (isset($payload['phone']) && is_string($payload['phone'])) {
            $info['phone'] = $payload['phone'];
        }

        if (isset($payload['two_fa_mode']) && is_string($payload['two_fa_mode'])) {
            $info['two_fa_mode'] = $payload['two_fa_mode'];
        }

        if (isset($payload['two_fa_time']) && is_string($payload['two_fa_time'])) {
            $info['two_fa_time'] = $payload['two_fa_time'];
        }

        if (isset($payload['aux_info']) && is_string($payload['aux_info'])) {
            $aux_info = json_decode($payload['aux_info'], true);
            if (is_array($aux_info)) {
                $info['aux_info'] = $aux_info;
            }
        }

        return $info;
    }

    /**
     * @param string|string[] $scope
     * @param string $token
     * @return bool
     */
    public function isScopeSupported($scope, $token)
    {
        $scopes = waUtils::toStrArray($scope);
        $scopes = array_unique($scopes);
        $info = $this->extractTokenInfo($token);
        $allowed_scopes = $info['scopes'];
        $diff = array_diff($scopes, $allowed_scopes);
        return empty($diff);
    }


    /**
     * Verify sign of token
     * @param string $token
     * @param string $secret
     * @return bool
     */
    public function verifyTokenSign($token, $secret)
    {
        if (!is_string($token)) {
            return false;
        }
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }

        $body = $parts[0] . '.' . $parts[1];
        $sign = $parts[2];

        $expected_sign = waUtils::urlSafeBase64Encode(hash_hmac('sha512', $body, $secret));
        return $sign === $expected_sign;
    }

    /**
     * Check token expiration
     * @param string $token
     * @param string $grace_interval
     * @return bool
     */
    public function isTokenExpired($token, $grace_interval = null)
    {
        $payload = $this->extractPayload($token);
        if (!$payload) {
            return true;
        }
        if (!isset($payload['exp']) || !wa_is_int($payload['exp']) || $payload['exp'] <= 0) {
            return true;
        }
        if (!wa_is_int($grace_interval) || $grace_interval < 0) {
            $grace_interval = 0;
        }
        return intval($payload['exp']) < ($this->getNowTime() - intval($grace_interval));
    }

    /**
     * Extract and decode payload from token
     * @param $token
     * @return mixed|null
     */
    protected function extractPayload($token)
    {
        if (!is_string($token)) {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return null;
        }
        $payload_json = waUtils::urlSafeBase64Decode($parts[1]);
        $payload = json_decode($payload_json, true);
        if (!is_array($payload)) {
            return null;
        }
        return $payload;
    }

    /**
     * Generate JWT ID
     * @return string
     */
    protected function generateJTI()
    {
        return waUtils::getRandomHexString(64);
    }

    /**
     * Get current unix timestamp
     * @return int
     */
    private function getNowTime()
    {
        return time();
    }
}
