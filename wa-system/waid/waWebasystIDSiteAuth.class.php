<?php

class waWebasystIDSiteAuth extends waWebasystIDAuthAdapter
{
    /**
     * Auth method
     * It can throw waWebasystIDAuthException on some this oauth2 related issues
     * It can standard waException on some unexpected situations
     * And finally on success must return access token params, with which waOAuthController will be work further
     *
     * @return array $params - access token params
     *      - string $params['access_token'] [required] - access token itself (jwt)
     *      - int    $params['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $params['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     *
     * @throws waException
     * @throws waWebasystIDAuthException
     * @throws waWebasystIDAccessDeniedAuthException
     *
     * If thrown waWebasystIDAuthException it is legit auth error, need to handle it
     */
    public function auth()
    {
        // error from webasyst ID server
        $error = waRequest::get('error');

        // auth code from webasyst ID server
        $code = waRequest::get('code');

        // it is beginning of auth process, adapter didn't ask webasyst ID server yet
        // redirect to provider auth page
        if (!$error && !$code) {
            $request_url = $this->getRedirectUri();
            wa()->getResponse()->redirect($request_url);
        }

        // auth server returns something be callback url
        $auth_response = $this->processAuthResponse();

        $user_data = $this->getUserData($auth_response);
        if (!$user_data) {
            throw new waAuthException("Can't get user info from Webasyst ID service", 500);
        }

        // Extract Webasyst contact
        $m = new waWebasystIDAccessTokenManager();
        $token_info = $m->extractTokenInfo($auth_response['access_token']);
        $contact_id = $token_info['contact_id'];

        $photo_url = null;
        if (!empty($user_data['userpic_uploaded'])) {
            $photo_url = $user_data['userpic_original_crop'];
        }

        unset(
            $user_data['userpic'],
            $user_data['userpic_uploaded'],
            $user_data['userpic_original_crop']
        );

        return array_merge($user_data, [
            'source' => $this->getId(),
            'source_id' => $contact_id,
            'photo_url' => $photo_url,
        ]);
    }

    protected function getCredentials()
    {
        return [
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret
        ];
    }

    public function getType()
    {
        return self::TYPE_SITE;
    }

    public function getControls()
    {
        return array(
            'app_id'     => _ws('Client ID'),
            'app_secret' => _ws('Client secret'),
        );
    }

    public function getName()
    {
        return 'Webasyst ID';
    }
}
