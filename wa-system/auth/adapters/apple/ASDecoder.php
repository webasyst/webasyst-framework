<?php

namespace AppleSignIn;

use AppleSignIn\Vendor\JWK;
use AppleSignIn\Vendor\JWT;

use Exception;

/**
 * Decode Sign In with Apple identity token, and produce an ASPayload for
 * utilizing in backend auth flows to verify validity of provided user creds.
 *
 * @package  AppleSignIn\ASDecoder
 * @author   Griffin Ledingham <gcledingham@gmail.com>
 * @license  http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link     https://github.com/GriffinLedingham/php-apple-signin
 */
class ASDecoder {
    /**
     * Parse a provided Sign In with Apple identity token.
     *
     * @param string $identityToken
     * @return object|null
     */
    public static function getAppleSignInPayload(string $identityToken) : ?object
    {
        $identityPayload = self::decodeIdentityToken($identityToken);
        return new ASPayload($identityPayload);
    }

    /**
     * Decode the Apple encoded JWT using Apple's public key for the signing.
     *
     * @param string $identityToken
     * @return object
     */
    public static function decodeIdentityToken(string $identityToken) : object {
        $publicKeyKid = JWT::getPublicKeyKid($identityToken);

        $publicKeyData = self::fetchPublicKey($publicKeyKid);

        $publicKey = $publicKeyData['publicKey'];
        $alg = $publicKeyData['alg'];

        $payload = JWT::decode($identityToken, $publicKey, [$alg]);

        return $payload;
    }

    /**
     * Fetch Apple's public key from the auth/keys REST API to use to decode
     * the Sign In JWT.
     *
     * @param string $publicKeyKid
     * @return array
     */
    public static function fetchPublicKey(string $publicKeyKid) : array {
        $publicKeys = file_get_contents('https://appleid.apple.com/auth/keys');
        $decodedPublicKeys = json_decode($publicKeys, true);

        if(!isset($decodedPublicKeys['keys']) || count($decodedPublicKeys['keys']) < 1) {
            throw new Exception('Invalid key format.');
        }

        $kids = array_column($decodedPublicKeys['keys'], 'kid');
        $parsedKeyData = $decodedPublicKeys['keys'][array_search($publicKeyKid, $kids)];
        $parsedPublicKey= JWK::parseKey($parsedKeyData);
        $publicKeyDetails = openssl_pkey_get_details($parsedPublicKey);

        if(!isset($publicKeyDetails['key'])) {
            throw new Exception('Invalid public key details.');
        }

        return [
            'publicKey' => $publicKeyDetails['key'],
            'alg' => $parsedKeyData['alg']
        ];
    }
}

/**
 * A class decorator for the Sign In with Apple payload produced by
 * decoding the signed JWT from a client.
 */
class ASPayload {
    protected $_instance;

    public function __construct(?object $instance) {
        if(is_null($instance)) {
            throw new Exception('ASPayload received null instance.');
        }
        $this->_instance = $instance;
    }

    public function __call($method, $args) {
        return call_user_func_array(array($this->_instance, $method), $args);
    }

    public function __get($key) {
        return (isset($this->_instance->$key)) ? $this->_instance->$key : null;
    }

    public function __set($key, $val) {
        return $this->_instance->$key = $val;
    }

    public function getEmail() : ?string {
        return (isset($this->_instance->email)) ? $this->_instance->email : null;
    }

    public function getUser() : ?string {
        return (isset($this->_instance->sub)) ? $this->_instance->sub : null;
    }

    public function verifyUser(string $user) : bool {
        return $user === $this->getUser();
    }
}
