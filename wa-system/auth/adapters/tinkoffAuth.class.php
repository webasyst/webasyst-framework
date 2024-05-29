<?php

/*
 * This file is part of Webasyst framework.
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2024 Webasyst LLC
 * @package wa-system
 * @subpackage auth
 */

/**
 * @see:  Documentation
 *
 * https://developer.tinkoff.ru/products/scenarios/TID/w2w
 */
class tinkoffAuth extends waOAuth2Adapter
{
    /** https://developer.tinkoff.ru/products/scenarios/TID/w2w#токены */
    const EXPIRES_IN = 1799;    //seconds
    const PATH_LOG = 'auth.log';
    const OAUTH_URL = 'https://id.tinkoff.ru/auth/authorize';
    const TOKEN_URL = 'https://id.tinkoff.ru/auth/token';
    const USER_INFO_URL = 'https://id.tinkoff.ru/userinfo/userinfo';

    protected $check_state = true;

    public function getName()
    {
        return (wa()->getLocale() == 'en_US' ? 'Tinkoff ID' : 'Тинькофф ID');
    }

    public function getControls()
    {
        return [
            'client_id' => _ws('Tinkoff client ID'),
            'client_secret' => _ws('Secret')
        ];
    }

    /**
     * Метод временный, для тестирования
     * @return string
     */
    public function getCallbackUrl($absolute = true)
    {
        return 'https://www.webasyst.com/cash-connector/tinkoff/';
    }

    public function getRedirectUri()
    {
        $redirect_uri = $this->getCallbackUrl();

        return self::OAUTH_URL
            .'?client_id='.$this->getOption('client_id')
            .'&redirect_uri='.urlencode($redirect_uri)
            .'&response_type=code';
    }

    public function getSessionState()
    {
        return waRequest::request('session_state', '');
    }

    public function getTokens($code)
    {
        static $params = [];
        if (empty($params)) {
            $headers = [
                // https://www.rfc-editor.org/rfc/rfc6749#section-2.3.1
                'Content-type: application/x-www-form-urlencoded',
                'Authorization: Basic '.base64_encode($this->getOption('client_id').':'.$this->getOption('client_secret'))
            ];
            $post_data = [
                'redirect_uri' => $this->getCallbackUrl(),
                'grant_type' => 'authorization_code',
                'session_state' => $this->getSessionState(),
                'client_id' => $this->getOption('client_id'),
                'code' => $code
            ];

            try {
                // сырой массив нельзя - tinkoff не понимает
                $response = $this->post(self::TOKEN_URL, http_build_query($post_data), $headers);
                $params = json_decode($response, true);
            } catch (Exception $exception) {
                waLog::log($exception->getMessage(), self::PATH_LOG);
            }
        }

        return $params;
    }

    /**
     * @param $code
     * @return mixed|null
     */
    public function getAccessToken($code)
    {
        $params = $this->getTokens($code);

        return ifset($params, 'access_token', null);
    }

    /**
     * @param $code
     * @return mixed|null
     */
    public function getRefreshToken($code)
    {
        $params = $this->getTokens($code);

        return ifset($params, 'refresh_token', null);
    }

    /**
     * @return int
     */
    public function getExpiryDate()
    {
        $params = $this->getTokens('');
        $expires_in = (int) ifset($params, 'expires_in', self::EXPIRES_IN);

        return time() + $expires_in;
    }

    /**
     * https://developer.tinkoff.ru/docs/api/tinkoff-id-informatsiya-o-polzovatele
     *
     * @param $token
     * @return array
     */
    public function getUserData($token)
    {
        $data = [];
        $options = [
            'format'         => waNet::FORMAT_JSON,
            'request_format' => waNet::FORMAT_RAW,
            'timeout'        => 60
        ];
        $headers = [
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => "Bearer $token"
        ];
        $post_fields = [
            'client_id' => $this->getOption('client_id'),
            'client_secret' => $this->getOption('client_secret')
        ];
        try {
            $net = new waNet($options, $headers);
            try {
                $response = (array) $net->query(self::USER_INFO_URL,  http_build_query($post_fields), waNet::METHOD_POST);
                $data = [
                    'source'    => 'tinkoff',
                    'source_id' => ifset($response, 'sub', ''),
                    'url'       => '',
                    'name'      => ifset($response, 'name', ''),
                    'firstname' => ifset($response, 'given_name', ''),
                    'lastname'  => ifset($response, 'family_name', '')
                ];
                if (!empty($response['gender'])) {
                    $data['sex'] = $response['gender'];
                }
                if (!empty($response['birthdate'])) {
                    $data['birthday'] = $response['birthdate'];
                }
                if (!empty($response['middle_name'])) {
                    $data['middlename'] = $response['middle_name'];
                }
                if (!empty($response['phone_number'])) {
                    $data['phone.home'] = $response['phone_number'];
                }
                if (!empty($response['email'])) {
                    $data['email'] = $response['email'];
                }
                $tokens = $this->saveTokens($data['source_id'], [
                    'tinkoff_token'         => (string) $token,
                    'tinkoff_token_refresh' => $this->getRefreshToken(''),
                    'tinkoff_token_expire'  => $this->getExpiryDate()
                ]);
                $data += ['tinkoff_tokens' => $tokens];
            } catch (Exception $ex) {
                $response = (array) $net->getResponse();
                waLog::log(['Error request User data', $response, $ex->getMessage()], self::PATH_LOG);
            }
        } catch (Exception $exception) {
            waLog::log($exception->getMessage(), self::PATH_LOG);
        }

        return $data;
    }

    /**
     * @param $tinkoff_id
     * @param $tinkoff_tokens
     * @return string
     * @throws waException
     */
    private function saveTokens($tinkoff_id, $tinkoff_tokens)
    {
        $result = '';
        if (empty($tinkoff_id)) {
            waLog::log('tinkoff_id is empty. Tokens are not saved', self::PATH_LOG);
            return $result;
        }
        $cdm = new waContactDataModel();
        $contact_data = $cdm->getByField([
            'field' => 'tinkoff_id',
            'value' => (string) $tinkoff_id,
            'sort'  => 0
        ]);

        $result = json_encode($tinkoff_tokens);
        if ($contact_data && !empty($tinkoff_tokens)) {
            $contact_id = (int) $contact_data['contact_id'];
            $contact_data = $cdm->getData($contact_id);
            if (isset($contact_data['tinkoff_tokens'])) {
                $cdm->updateByField([
                    'field'      => 'tinkoff_tokens',
                    'contact_id' => $contact_id
                ], ['value' => $result]);
            } else {
                $cdm->insert([
                    'field'      => 'tinkoff_tokens',
                    'contact_id' => $contact_id,
                    'value'      => $result
                ]);
            }
        }

        return $result;
    }

    /**
     * @param $contact_id
     * @return array
     */
    public function refreshToken($contact_id)
    {
        $result = [];
        if (empty($contact_id)) {
            waLog::log('Refresh token. Empty contact id', self::PATH_LOG);
            return $result;
        }

        try {
            $contact = new waContact($contact_id);
            if (!$contact->exists()) {
                waLog::log('Refresh token. Contact do not exists. Contact ID '.$contact_id, self::PATH_LOG);
                return $result;
            } elseif (!$tokens_json = $contact->get('tinkoff_tokens')) {
                waLog::log('Refresh token. Contact is missing a token. Contact ID '.$contact_id, self::PATH_LOG);
                return $result;
            }
            $refresh_tokens = json_decode($tokens_json, true);
            $options = [
                'format'         => waNet::FORMAT_JSON,
                'request_format' => waNet::FORMAT_RAW,
                'timeout'        => 60
            ];
            $headers = [
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode($this->getOption('client_id').':'.$this->getOption('client_secret'))
            ];
            $post_fields = [
                'grant_type'    => 'refresh_token',
                'refresh_token' => ifset($refresh_tokens, 'tinkoff_token_refresh', '')
            ];

            $net = new waNet($options, $headers);
            $response = (array) $net->query(self::TOKEN_URL, http_build_query($post_fields), waNet::METHOD_POST);
            $result = [
                'tinkoff_token'         => ifset($response, 'access_token', ''),
                'tinkoff_token_refresh' => ifset($response, 'refresh_token', ''),
                'tinkoff_token_expire'  => time() + (int) ifset($response, 'expires_in', self::EXPIRES_IN)
            ];
            $this->saveTokens($contact->get('tinkoff_id'), $result);
        } catch (Exception $ex) {
            waLog::log($ex->getMessage(), self::PATH_LOG);
        }

        return $result;
    }
}
