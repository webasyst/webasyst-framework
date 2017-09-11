<?php

class teamGooglecalendarDriver
{
    /**
     * @var teamGooglecalendarCurl
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
        $this->curl = new teamGooglecalendarCurl(array(
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

        $this->beforeRefreshToken();

        // new disk instance, without error handler. To prevent recursion
        $oauth = new teamGooglecalendarOauth($client_id, $client_secret);
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
        $res = $this->curl->get('https://www.googleapis.com/calendar/v3/users/me/calendarList');
        if (empty($res['body'])) {
            return array();
        }
        $this->checkError($res['body']);
        return $res['body'];
    }

    public function addEvent($calendar_id, $data, $options = array())
    {
        return $this->expirationTokenGuard(
            array( array($this, 'addEventOperation'), array($calendar_id, $data, $options) )
        );
    }

    public function addEventOperation($calendar_id, $data, $options = array())
    {
        $calendar_id_encoded = urlencode($calendar_id);
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id_encoded}/events";
        $res = $this->curl->post($url, array(
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($data)
        ));
        $this->checkError($res['body']);
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
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id_encoded}/events/{$event_id_encoded}";
        $res = $this->curl->patch($url, array(
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($data)
        ));
        $this->checkError($res['body']);
        if (!empty($res['body']['status']) && $res['body']['status'] === 'cancelled') {
            throw new teamCalendarExternalEventNotFoundException();
        }
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
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id_encoded}/events/{$event_id_encoded}";
        $res = $this->curl->delete($url);
        $this->checkError($res['body']);
        return $res['body'];
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
        foreach (array('timeMin', 'pageToken', 'syncToken') as $field) {
            if (isset($options[$field])) {
                $params[$field] = $options[$field];
            }
        }

        if (isset($params['syncToken']) && isset($params['timeMin'])) {
            unset($params['timeMin']);
        }

        $calendar_id_encoded = urlencode($calendar_id);
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id_encoded}/events";
        if ($params) {
            $url .= "?" . http_build_query($params);
        }
        $res = $this->curl->get($url);
        $this->checkError($res['body']);
        return $res['body'];
    }

    private function checkError($data)
    {
        if (empty($data['error'])) {
            return null;
        }

        $error_message = 'Unknown error.';
        if (!empty($data['message'])) {
            $error_message = $data['message'];
        } else if (!empty($data['error']['message'])) {
            $error_message = $data['error']['message'];
        } else if (!empty($data['error']['errors']) && is_array($data['error']['errors'])) {
            $errors = array();
            foreach ($data['error']['errors'] as $er) {
                $msg = '';
                if (!empty($er['message'])) {
                    $msg = $er['message'];
                } else {
                    $msg = var_export($er, true);
                }
                $errors[] = $msg;
            }
            $error_message = join(PHP_EOL, $errors);
        }

        $e = new teamGooglecalendarDriverException($error_message);

        if ($error_message === 'Invalid Credentials') {
            $e = new teamCalendarExternalTokenInvalidException($error_message);
        } else if ($error_message === 'Resource has been deleted' || $error_message === 'Not Found') {
            $e = new teamCalendarExternalEventNotFoundException($error_message);
        }

        throw $e;
    }
}
