<?php

class teamGooglecalendarOauth
{
    /**
     * @var teamGooglecalendarCurl
     */
    protected $curl;

    /**
     * @var string
     */
    private $client_id;

    /**
     * @var string
     */
    private $client_secret;

    public function __construct($client_id, $client_secret)
    {
        if (!$client_id) {
            throw new teamGooglecalendarOauthException('Client ID is required');
        }
        $this->client_id = $client_id;

        if (!$client_secret) {
            throw new teamGooglecalendarOauthException('Client secret is required');
        }
        $this->client_secret = $client_secret;

        $this->curl = new teamGooglecalendarCurl();
    }

    public function refreshToken($refresh_token)
    {
        $url = 'https://www.googleapis.com/oauth2/v4/token';

        $post_fields = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        );

        $post_fields = http_build_query($post_fields);
        $res = $this->curl->post($url, array(
            CURLOPT_POSTFIELDS => $post_fields
        ));
        return ifset($res['body']['access_token']);
    }

    public function authorizeBegin($calendar_external_id, $options = array())
    {
        // csrf protection
        $secure_hash = md5(uniqid(time(), true));
        wa()->getStorage()->set('team/plugins/googlecalendar/secure_hash', $secure_hash);

        $state = substr($secure_hash, 0, 16) . $calendar_external_id . substr($secure_hash, 16);

        $params = array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'state' => $state,
            'scope' => 'profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/calendar',
            'redirect_uri' => $options['redirect_uri'],
            'access_type' => 'offline',
            'prompt' => 'consent'
        );

        $form_id = uniqid('t-googlecalendar-authorize-form');
        $url = 'https://accounts.google.com/o/oauth2/v2/auth';

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

        $secure_hash = wa()->getStorage()->get('team/plugins/googlecalendar/secure_hash');
        $received_hash = substr($state, 0, 16) . substr($state, -16);
        if ($received_hash !== $secure_hash) {
            $e = new teamGooglecalendarOauthException(_wp("Session protection validated"));
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

        $error = ifset($res['error']);
        if ($error) {
            $msg = $error;
            if ($error === 'access_denied') {
                $msg = _wp('Access denied');
            } else if (!empty($res['error_description'])) {
                $msg = $res['error_description'];
            }
            $e = new teamGooglecalendarOauthException($msg);
            $e->setParams(array(
                'id' => $calendar_external_id
            ));
            throw $e;
        }

        $code = ifset($res['code']);
        if (!$code) {
            $e = new teamGooglecalendarOauthException(_wp("Code for generating token is not received"));
            $e->setParams(array(
                'id' => $calendar_external_id
            ));
            throw $e;
        }

        $url = 'https://www.googleapis.com/oauth2/v4/token';

        $post_fields = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        );
        if (!empty($options['redirect_uri'])) {
            $post_fields['redirect_uri'] = $options['redirect_uri'];
        }

        $post_fields = http_build_query($post_fields);

        $result = $this->curl->post($url, array(
            CURLOPT_POSTFIELDS => $post_fields
        ));


        if ($result['http_code'] != 200 || empty($result['body']['access_token'])) {
            $msg = _wp("Token is not generated");
            if (!empty($result['body']['error_description'])) {
                $msg = $result['body']['error_description'];
            } else if (!empty($result['body']['error'])) {
                $msg = $result['body']['error'];
            }
            $e = new teamGooglecalendarOauthException($msg);
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

    public function getUserInfo($token)
    {
        $res = $this->curl->get(
            "https://www.googleapis.com/oauth2/v2/userinfo?fields=email,id,name",
            array(
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token
                )
            )
        );
        return $res['body'];
    }
}