<?php

class teamOffice365Oauth
{
    private static $plugin_id = 'office365';

    /**
     * @var string
     */
    protected $client_id;

    /**
     * @var string
     */
    protected $client_secret;

    /**
     * @var string
     */
    protected $redirect_uri;

    /**
     * @var teamOffice365Curl
     */
    protected $curl;

    protected $url = 'https://login.microsoftonline.com/common/oauth2/v2.0';

    protected $scope = 'User.Read Calendars.ReadWrite offline_access openid profile email';

    public function __construct($options = array())
    {
        $this->client_id = ifset($options['client_id']);
        if (!$this->client_id) {
            throw new teamOffice365OauthException('Client ID is required');
        }

        $this->client_secret = ifset($options['client_secret']);
        if (!$this->client_secret) {
            throw new teamOffice365OauthException('Client secret is required');
        }

        $this->redirect_uri = ifset($options['redirect_uri']);
        if (!$this->redirect_uri) {
            throw new teamOffice365OauthException('Redirect uri is required');
        }

        $this->curl = new teamOffice365Curl();
    }

    public function authorizeBegin($calendar_external_id)
    {
        $plugin_id = self::$plugin_id;

        // csrf protection
        $secure_hash = md5(uniqid(time(), true));
        wa()->getStorage()->set("team/plugins/{$plugin_id}/secure_hash", $secure_hash);

        $state = substr($secure_hash, 0, 16) . $calendar_external_id . substr($secure_hash, 16);

        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => $this->scope,
            'state' => $state,
        );

        $form_id = uniqid("t-{$plugin_id}-authorize-form");
        $url = $this->url . '/authorize';

        $html = "<form id='{$form_id}' action='{$url}' method='GET'>";
        foreach ($params as $key => $value) {
            $html .= "<input type='hidden' name='{$key}' value='{$value}'>";
        }
        $html .= "</form>";
        $html .= "<script>
            $(function() {
                $('#{$form_id}').submit();
            });
        </script>";

        return $html;
    }

    public function authorizeEnd($options = array())
    {
        $plugin_id = self::$plugin_id;

        $query = ifset($options['query'], '');
        if (!$query) {
            $server = wa()->getRequest()->server();
            if (isset($server['QUERY_STRING'])) {
                $query = $server['QUERY_STRING'];
            } elseif (isset($server['REDIRECT_QUERY_STRING'])) {
                $query = $server['REDIRECT_QUERY_STRING'];
            } elseif (isset($server['REQUEST_URI'])) {
                $info = parse_url($server['REQUEST_URI']);
                $query = $info['query'];
            }
        }

        parse_str($query, $res);

        $state = ifset($res['state']);
        $calendar_external_id = substr($state, 16, -16);

        $secure_hash = wa()->getStorage()->get("team/plugins/{$plugin_id}/secure_hash");
        $received_hash = substr($state, 0, 16) . substr($state, -16);
        if ($received_hash !== $secure_hash) {
            $e = new teamOffice365OauthException(_wp("Session protection validated"));
            $e->setParams(array(
                'id' => $calendar_external_id
            ));
            throw $e;
        }

        // token already received, just return
        if (ifset($res['token'])) {
            return array(
                'id' => $calendar_external_id,
                'token' => $res['token']
            );
        }

        $code = ifset($res['code']);
        if (!$code) {
            if (!waRequest::isHttps()) {
                $e = new teamOffice365OauthException(_wp("Code for generating token is not received. Use HTTPS redirect URL for Office365"));
            } else {
                $e = new teamOffice365OauthException(_wp("Code for generating token is not received"));
            }
            $e->setParams(array(
                'id' => $calendar_external_id
            ));
            throw $e;
        }

        $post_fields = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'grant_type' => 'authorization_code',
            'scope' => $this->scope
        );

        $post_fields = http_build_query($post_fields);

        $result = $this->curl->post($this->url . '/token', array(
            CURLOPT_POSTFIELDS => $post_fields
        ));

        if ($result['http_code'] != 200 || empty($result['body']['access_token'])) {
            $msg = _wp("Token is not generated");
            if (!empty($result['body']['error_description'])) {
                $msg = $result['body']['error_description'];
            } else if (!empty($result['body']['error'])) {
                $msg = $result['body']['error'];
            }
            $e = new teamOffice365OauthException($msg);
            $e->setParams(array(
                'id' => $calendar_external_id
            ));
            throw $e;
        }

        return array(
            'id' => $calendar_external_id,
            'token' => $result['body']['access_token'],
            'refresh_token' =>  ifset($result['body']['refresh_token'], '')
        );
    }

    public function refreshToken($refresh_token)
    {
        $post_fields = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
            'redirect_uri' => $this->redirect_uri,
            'scope' => $this->scope
        );

        $post_fields = http_build_query($post_fields);
        $res = $this->curl->post($this->url . '/token', array(
            CURLOPT_POSTFIELDS => $post_fields
        ));
        return ifset($res['body']['access_token']);
    }

    public function getUserInfo($token)
    {
        $res = $this->curl->get(
            'https://graph.microsoft.com/v1.0/me',
            array(
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                )
            )
        );
        return $res['body'];
    }
}
