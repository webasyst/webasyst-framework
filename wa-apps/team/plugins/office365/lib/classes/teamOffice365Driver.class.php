<?php

class teamOffice365Driver
{
    /**
     * @var teamOffice365Curl
     */
    protected $curl;

    protected $token = '';

    protected $options = array();

    public function __construct($token, $options = array())
    {
        $this->options = $options;
        $this->token = $token;
        $this->initCurl();
    }

    protected function initCurl()
    {
        $this->curl = new teamOffice365Curl(array(
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->token
            )
        ));
    }

    protected function refreshToken()
    {
        $refresh_token = !empty($this->options['refresh_token']) ? $this->options['refresh_token'] : '';
        if (!$refresh_token) {
            return false;
        }

        $client_id = !empty($this->options['client_id']) ? $this->options['client_id'] : '';
        $client_secret = !empty($this->options['client_secret']) ? $this->options['client_secret'] : '';
        $redirect_uri = !empty($this->options['redirect_uri']) ? $this->options['redirect_uri'] : '';

        $this->beforeRefreshToken();

        // new disk instance, without error handler. To prevent recursion
        $oauth = new teamOffice365Oauth(array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri
        ));
        $token = $oauth->refreshToken($refresh_token);

        $this->token = $token;

        $this->afterRefreshToken();

        if (!$token) {
            return false;
        }

        $this->token = $token;
        $this->initCurl();

        return $this->token;
    }


    public function beforeRefreshToken()
    {
        if (isset($this->options['beforeRefreshTokenListener']) && is_array($this->options['beforeRefreshTokenListener'])) {
            foreach ($this->options['beforeRefreshTokenListener'] as $listener) {
                if (is_callable($listener)) {
                    call_user_func($listener, $this->token);
                }
            }
        }
    }

    public function afterRefreshToken()
    {
        if (isset($this->options['afterRefreshTokenListener']) && is_array($this->options['afterRefreshTokenListener'])) {
            foreach ($this->options['afterRefreshTokenListener'] as $listener) {
                if (is_callable($listener)) {
                    call_user_func($listener, $this->token);
                }
            }
        }
    }

    protected function expirationTokenGuard($body_function_context, $on_throw_function_context = array())
    {
        $bf = isset($body_function_context[0]) && is_callable($body_function_context[0]) ? $body_function_context[0] : null;
        $bf_params = ifset($body_function_context[1], array());
        $otf = isset($on_throw_function_context[0]) && is_callable($on_throw_function_context[0]) ? $on_throw_function_context[0] : null;
        $otf_params = ifset($on_throw_function_context[1], array());

        try {
            if ($bf) {
                return call_user_func_array($bf, $bf_params);
            }
        } catch (teamCalendarExternalTokenInvalidException $e) {
            try {
                if ($this->refreshToken()) {
                    if ($bf) {
                        return call_user_func_array($bf, $bf_params);
                    }
                } else {
                    if ($otf) {
                        return call_user_func_array($otf, $otf_params);
                    }
                    throw $e;
                }
            } catch (Exception $e) {
                if ($otf) {
                    return call_user_func_array($otf, $otf_params);
                }
                throw $e;
            }
        } catch (Exception $e) {
            if ($otf) {
                return call_user_func_array($otf, $otf_params);
            }
            throw $e;
        }
    }

    public function getCalendars($options = array())
    {
        return $this->expirationTokenGuard(
            array( array($this, 'getCalendarsOperation'), array($options) )
        );
    }

    protected function getCalendarsOperation($options = array())
    {
        $url = 'https://graph.microsoft.com/v1.0/me/calendars';
        $res = $this->curl->get($url);
        $this->checkError($res);
        return (array) ifset($res['body']);
    }

    public function getEvents($calendar_id, $options = array())
    {
        return $this->expirationTokenGuard(
            array( array($this, 'getEventsOperation'), array($calendar_id, $options) )
        );
    }

    protected function getEventsOperation($calendar_id, $options = array())
    {
        $params = array();
        if (isset($options['startDateTime'])) {
            $params['startDateTime'] = $options['startDateTime'];
        }
        if (isset($options['endDateTime'])) {
            $params['endDateTime'] = $options['endDateTime'];
        }

        $calendar_id_encoded = urlencode($calendar_id);

        $url = "https://graph.microsoft.com/v1.0/me/calendars/{$calendar_id_encoded}/calendarView";
        if ($params) {
            $url .= "?" . http_build_query($params);
        }
        $res = $this->curl->get($url);
        $this->checkError($res);
        return $res['body'];
    }

    public function addEvent($calendar_id, $data, $options = array())
    {
        return $this->expirationTokenGuard(
            array( array($this, 'addEventOperation'), array($calendar_id, $data, $options) )
        );
    }

    protected function addEventOperation($calendar_id, $data, $options = array())
    {
        $calendar_id_encoded = urlencode($calendar_id);
        $url = "https://graph.microsoft.com/v1.0/me/calendars/{$calendar_id_encoded}/events";
        $res = $this->curl->post($url, array(
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($data)
        ));
        $this->checkError($res);
        return $res['body'];
    }

    public function updateEvent($calendar_id, $event_id, $data, $options = array())
    {
        return $this->expirationTokenGuard(
            array( array($this, 'updateEventOperation'), array($calendar_id, $event_id, $data, $options) )
        );
    }

    protected function updateEventOperation($calendar_id, $event_id, $data, $options = array())
    {
        $calendar_id_encoded = urlencode($calendar_id);
        $event_id_encoded = urlencode($event_id);
        $url = "https://graph.microsoft.com/v1.0/me/calendars/{$calendar_id_encoded}/events/{$event_id_encoded}";
        $res = $this->curl->patch($url, array(
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($data)
        ));
        $this->checkError($res);
        return $res['body'];
    }

    public function deleteEvent($calendar_id, $event_id, $options = array())
    {
        return $this->expirationTokenGuard(
            array( array($this, 'deleteEventOperation'), array($calendar_id, $event_id, $options) )
        );
    }

    protected function deleteEventOperation($calendar_id, $event_id, $data, $options = array())
    {
        $calendar_id_encoded = urlencode($calendar_id);
        $event_id_encoded = urlencode($event_id);
        $url = "https://graph.microsoft.com/v1.0/me/calendars/{$calendar_id_encoded}/events/{$event_id_encoded}";
        $res = $this->curl->delete($url);
        $this->checkError($res);
        return $res['body'];
    }

    private function checkError($res)
    {
        if ($res['http_code'] == 401) {
            $msg = '';
            foreach ($res['response']['headers'] as $header) {
                if (strpos($header, 'x-ms-diagnostics:') !== false) {
                    $res = $this->parseXMsDiagnosticsHeader($header);
                    $reason = ifset($res['reason'], '');
                    if ($reason) {
                        $msg = $reason;
                    } else {
                        $error_code = ifset($res[0]);
                        $error_category = ifset($res['error_category'], '');
                        $msg = sprintf('%s #%s', $error_category, $error_code);
                    }
                    break;
                }
            }
            if (!$msg) {
                $msg = 'Access allowed only for registered users.';
            }
            throw new teamCalendarExternalTokenInvalidException($msg);
        }
    }

    private function parseXMsDiagnosticsHeader($header)
    {
        $parts = explode(':', $header, 2);
        $header = trim(ifset($parts[1], ''));

        $result = array();
        foreach (explode(';', $header) as $item) {
            if (strpos($item, '=') === false) {
                $result[] = $item;
            } else {
                $parts = explode('=', $item, 2);
                $result[trim($parts[0])] = trim(trim(ifset($parts[1])), '"');
            }
        }

        return $result;
    }
}