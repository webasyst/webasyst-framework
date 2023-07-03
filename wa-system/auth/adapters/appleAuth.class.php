<?php

use AppleSignIn\ASDecoder;

class appleAuth extends waOAuth2Adapter
{
    const AUTHORIZE_URL = 'https://appleid.apple.com/auth/authorize';
    const TOKEN_URL     = 'https://appleid.apple.com/auth/token';
    const LOG_FILE = 'auth/apple.log';

    // we check state by hand in our own code in order to be able to log and debug when something goes wrong
    protected $check_state = false;

    /**
     * appleAuth constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $vendor_dir = dirname(__FILE__).'/apple/';
        require_once($vendor_dir.'vendor/JWK.php');
        require_once($vendor_dir.'vendor/JWT.php');
        require_once($vendor_dir.'ASDecoder.php');
        $this->options['redirect_uri'] = sprintf('%soauth.php/?provider=%s', wa()->getRootUrl(true), $this->getId());

        //$options['debug_log'] = 1;

        parent::__construct($options);
    }

    /**
     * @return array
     */
    public function getControls()
    {
        return [
            'client_id' => _ws('Client ID')
                .'<br><span class="hint">'.
                    _ws('Identifier (App ID or Services ID)')
                .'</span>',

/*
            'apple_team_id' => ws('Team ID')
                .'<br><span class="hint">'.
                    ws('10-character Team ID associated with your developer account')
                .'</span>',

            'apple_key_id' => ws('Key ID')
                .'<br><span class="hint">'.
                    ws('10-character key identifier generated for the Sign in with Apple private key associated with your developer account')
                .'</span>',

            // first line of the file starts with -----BEGIN PRIVATE KEY-----
            'private_key' => ws('Private Key')
                .'<br><span class="hint">'.
                    ws('File path to a .p8 file containing private key used to sign JSON Web Tokens')
                .'</span>',
*/
        ];
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        $state = md5(uniqid(rand(), true));
        wa()->getStorage()->set('auth_state', $state);

        $params = [
            // ask for id_token here to make it available during getAccessToken() without API request
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'client_id'     => $this->getOption('client_id'),
            'redirect_uri'  => $this->getOption('redirect_uri'),
            'scope'         => 'name email',
            'state'         => $state,
        ];

        $url = sprintf(
            '%s?%s',
            self::AUTHORIZE_URL,
            http_build_query($params)
        );

        return $url;
    }

    /**
     * @param $code
     *
     * @return array|SimpleXMLElement|string|null
     * @throws waException
     */
    public function getAccessToken($code)
    {
        // Check state
        $state = wa()->getStorage()->get('auth_state');
        if (!$state || $state !== waRequest::request('state')) {
            // Enforcing state validation causes problems when
            // user has several sessions in many tabs in the same browser.
            //return null;
        }

        $this->debugLog('request data @ getAccessToken', waRequest::request());

        // Check identity token from POST data. No API access needed.
        // Of all the plugin options, only client_id is actually used...
        try {
            $identityToken = waRequest::request('id_token', '', 'string');
            if ($identityToken) {
                $appleSignInPayload = ASDecoder::getAppleSignInPayload($identityToken);
                $email = $appleSignInPayload->getEmail();
                if ($email) {
                    return [
                        'id_token' => $identityToken,
                        'apple_payload' => $appleSignInPayload,
                    ];
                }
            }
        } catch (Exception $ex) {
            $this->errorLog('Error handling identity token from GET/POST', $ex->getMessage(), $ex->getTraceAsString(), waRequest::request());
        }

        // If id_token is absent or expired, callback is not from Apple and we don't trust it.
        return null;

        // Alternative plan might have been to use $code to get user data via Apple API.
        // This requires quite a lot more plugin settings though.
        try {
            $jwt = $this->generateJWT(
                $this->getOption('apple_key_id'),
                $this->getOption('apple_team_id'),
                $this->getOption('client_id'),
                $this->getPrivateKey()
            );

            $params = [
                'client_id'     => $this->getOption('client_id'),
                'client_secret' => $jwt,
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $this->getOption('redirect_uri'),
            ];

            $this->debugLog('request token params', $params);

            $net = new waNet([
                'format'         => waNet::FORMAT_JSON,
                'request_format' => waNet::FORMAT_RAW,
            ]);
            try {
                $response = $net->query(self::TOKEN_URL, $params, waNet::METHOD_POST);
                $this->debugLog('request token response', $net->getResponseHeader(), $response);
            } catch (waException $e) {
                if ($e->getCode() == 400) {
                    $response = json_decode($net->getResponse(true), true);
                    if (ifset($response, 'error', '') === 'invalid_grant') {
                        // The code has expired or has been revoked.
                        $this->debugLog('request token failed: invalid_grant');
                        return null;
                    }
                }
                $this->errorLog('request token exception', $e->getMessage(), $e->getCode(), $net->getResponseDebugInfo());
                return null;
            }

            if ($response && !empty($response['access_token']) && !empty($response['id_token'])) {
                $response['apple_payload'] = ASDecoder::getAppleSignInPayload($response['id_token']);
                if ($response['apple_payload']->getEmail()) {
                    return $response;
                } else {
                    $this->errorLog('token response payload does not contain email');
                    return null;
                }
            }

            $this->errorLog('response token error', $response);
        } catch (Exception $ex) {
            $this->errorLog('Error during getAccessToken API call', $ex->getMessage(), $ex->getTraceAsString());
        }

        return null;
    }

    protected function getPrivateKey()
    {
        $option_private_key = $this->getOption('private_key');

        // Parse private key if inserted to config all in one line
        $pkstart = '-----BEGIN PRIVATE KEY-----';
        $pkend = '-----END PRIVATE KEY-----';
        if (substr($option_private_key, 0, strlen($pkstart)) === $pkstart) {
            $key = rtrim($option_private_key);
            if (substr($key, -strlen($pkend)) === $pkend) {
                $key = trim(substr($key, strlen($pkstart), -strlen($pkend)));
                $key = preg_replace('~\s+~', "\n", trim($key));
                return "{$pkstart}\n{$key}\n{$pkend}";
            }
        }

        foreach([
            $option_private_key,
            wa()->getConfig()->getRootPath().'/'.ltrim($option_private_key, '/')
        ] as $path) {
            if (file_exists($path)) {
                if (is_readable($path)) {
                    return file_get_contents($path);
                }
                throw new waException('Unable to read private key file');
            }
        }
        throw new waException('Private key file not found');
    }

    /**
     * @param $response non-empty return value from getAccessToken() above
     * @return array contact data
     */
    public function getUserData($response)
    {
        $this->debugLog('response data @ getUserData', $response);

        $data = [
            'source'    => 'apple',
            'source_id' => $response['apple_payload']->sub,
            'email'     => $response['apple_payload']->getEmail(),
        ];

        // User name will only exist in POST in case this is the first auth of this apple user.
        // If it's not there, nothing we can do at this point.
        $user_json = waRequest::post('user', null, 'string');
        if ($user_json) {
            $user = json_decode($user_json, true);
            if ($user) {
                $data['firstname'] = ifset($user, 'name', 'firstName', null);
                $data['lastname'] = ifset($user, 'name', 'lastName', null);
            }
        }

        return $data;
    }

    protected static function base64urlEncode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    protected static function jsonFromArray($arr)
    {
        return json_encode($arr, JSON_UNESCAPED_SLASHES);
    }

    protected static function generateJWT($kid, $iss, $sub, $key)
    {
        $payload = self::base64urlEncode(self::jsonFromArray([
            'kid' => $kid,
            'alg' => 'ES256',
        ]));
        $payload .= '.';
        $payload .= self::base64urlEncode(self::jsonFromArray([
            'iss' => $iss,
            'iat' => time(),
            'exp' => time() + 300,
            'aud' => 'https://appleid.apple.com',
            'sub' => $sub,
        ]));

        $signature = self::signES256($payload, $key);
        return $payload.'.'.self::base64urlEncode($signature);
    }

    protected static function signES256($payload, $key)
    {
        //$privKey = openssl_pkey_get_private($key);
        $privKey = openssl_get_privatekey($key, null);
        if (!$privKey) {
            throw new waException('Unable to process private key.');
        }

        $success = openssl_sign($payload, $signature, $privKey, "sha256"); // OPENSSL_ALGO_SHA256 ?..
        if (!$success) {
            throw new waException('Unable to generate signature.');
        }

        return self::convertUnpack($signature);
    }

    protected static function convertUnpack($signature)
    {
        // DER unpacking from https://github.com/firebase/php-jwt
        $pos = 0;
        $components = [];
        $size = strlen($signature);
        while ($pos < $size) {
            $constructed = (ord($signature[$pos]) >> 5) & 0x01;
            $type = ord($signature[$pos++]) & 0x1f;
            $len = ord($signature[$pos++]);
            if ($len & 0x80) {
                $n = $len & 0x1f;
                $len = 0;
                while ($n-- && $pos < $size) {
                    $len = ($len << 8) | ord($signature[$pos++]);
                }
            }

            if ($type == 0x03) {
                $pos++;
                $components[] = substr($signature, $pos, $len - 1);
                $pos += $len - 1;
            } else if (!$constructed) {
                $components[] = substr($signature, $pos, $len);
                $pos += $len;
            }
        }
        foreach ($components as &$c) {
            $c = str_pad(ltrim($c, "\x00"), 32, "\x00", STR_PAD_LEFT);
        }
        return implode('', $components);
    }

    protected function errorLog($msg)
    {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $args[] = self::LOG_FILE;
            call_user_func_array('waLog::dump', $args);
        } else {
            waLog::log($msg, self::LOG_FILE);
        }
    }

    protected function debugLog($msg)
    {
        if (!$this->getOption('debug_log')) {
            return;
        }

        if (func_num_args() > 1) {
            $args = func_get_args();
            $args[] = self::LOG_FILE;
            call_user_func_array('waLog::dump', $args);
        } else {
            waLog::log($msg, self::LOG_FILE);
        }
    }
}
