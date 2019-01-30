<?php

class teamCaldavClient
{
    protected $url;
    protected $login;
    protected $password;
    protected $options;

    protected $user_agent = 'Webasyst team app caldav plugin';

    /**
     * @var teamCaldavCurl
     */
    protected $curl;

    public function __construct($url, $login, $password, $options = array())
    {
        $this->url = rtrim($url, '/') . '/';
        $this->login = $login;
        $this->password = $password;
        $this->options = (array) $options;
        $this->curl = new teamCaldavCurl(array(
            CURLOPT_USERPWD => $this->login . ':' . $this->password,
            CURLOPT_HTTPHEADER => array(
                'User-Agent: ' . $this->user_agent
            )
        ));
    }

    public function checkConnection()
    {
        $res = $this->curl->options($this->url);

        $this->checkError($res, false);

        $is_caldav = false;

        $response_headers = ifset($res['response_headers'], array());
        foreach ($response_headers as $header) {
            $is_dav = strtolower(substr($header, 0, 4)) === 'dav:';
            $is_caldav = $is_dav && strstr($header, 'calendar-access') !== false;
            if ($is_dav) {
                break;
            }
        }

        if (!$is_caldav) {
            throw new teamCaldavClientException("It's not CalDAV Server");
        }

        return true;
    }

    /**
     * @param string $url
     * @param int $depth
     * @return SimpleXMLElement
     */
    public function allProp($url = '', $depth = 0)
    {
        $url = $this->buildUrl($url);
        $xml = '<propfind xmlns="DAV:"><allprop/></propfind>';
        $res = $this->curl->propfind($url, $xml, array(
            CURLOPT_HTTPHEADER => array(
                'Depth: ' . $depth
            )
        ));

        $this->checkError($res);
        $message = $res['body']['message'];
        return new SimpleXMLElement($message);
    }

    public function reportCalendarData($url = '', $filter = array(), $depth = 0)
    {
        $url = $this->buildUrl($url);

        $filter_xml = '';
        if ($filter) {
            $filter_xml = '<c:filter>
                <c:comp-filter name="VCALENDAR">
                    <c:comp-filter name="VEVENT">
                        :time_range
                    </c:comp-filter>
                </c:comp-filter>
            </c:filter>';
            foreach (array('time_range') as $var) {
                $node = '';
                if (isset($filter[$var]) && is_array($filter[$var])) {
                    $attributes = array();
                    foreach ($filter[$var] as $attr_name => $attr_value) {
                        $attributes[] = "{$attr_name}=\"{$attr_value}\"";
                    }
                    $tag = str_replace('_', '-', $var);
                    $node = '<c:' . $tag . ' ' . join(' ', $attributes) . ' />';
                }
                $filter_xml = str_replace(':' . $var, $node, $filter_xml);
            }
        }

        $xml = '<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
                    <d:prop>
                        <d:getetag />
                        <c:calendar-data />
                    </d:prop>'
                    . $filter_xml .
                '</c:calendar-query>';

        $res = $this->curl->report($url, $xml, array(
            CURLOPT_HTTPHEADER => array(
                'Depth: ' . $depth
            )
        ));
        $this->checkError($res);
        $message = $res['body']['message'];
        return new SimpleXMLElement($message);
    }

    public function multigetCalendarData($calendar_url, $event_urls = array(), $depth = 1)
    {
        $calendar_url = $this->buildUrl($calendar_url);

        $xml = '<c:calendar-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
                    <d:prop>
                        <d:getetag />
                        <c:calendar-data />
                    </d:prop>
                    :hrefs
                </c:calendar-multiget>';

        $hrefs = array();
        foreach ($event_urls as $href) {
            $hrefs[] = '<d:href>' . $href . '</d:href>';
        }
        $hrefs = join(PHP_EOL, $hrefs);

        $xml = str_replace(':hrefs', $hrefs, $xml);

        $res = $this->curl->report($calendar_url, $xml, array(
            CURLOPT_HTTPHEADER => array(
                'Depth: ' . $depth
            )
        ));
        $this->checkError($res);
        $message = $res['body']['message'];
        return new SimpleXMLElement($message);
    }

    public function reportCalendarChangedTag($url = '', $filter = array(), $depth = 0)
    {
        $url = $this->buildUrl($url);

        $filter_xml = '<c:filter>
                <c:comp-filter name="VCALENDAR">
                    <c:comp-filter name="VEVENT" />
                </c:comp-filter>
            </c:filter>';
        if ($filter) {
            $filter_xml = '<c:filter>
                <c:comp-filter name="VCALENDAR">
                    <c:comp-filter name="VEVENT">
                        :time_range
                    </c:comp-filter>
                </c:comp-filter>
            </c:filter>';
            foreach (array('time_range') as $var) {
                $node = '';
                if (isset($filter[$var]) && is_array($filter[$var])) {
                    $attributes = array();
                    foreach ($filter[$var] as $attr_name => $attr_value) {
                        $attributes[] = "{$attr_name}=\"{$attr_value}\"";
                    }
                    $tag = str_replace('_', '-', $var);
                    $node = '<c:' . $tag . ' ' . join(' ', $attributes) . ' />';
                }
                $filter_xml = str_replace(':' . $var, $node, $filter_xml);
            }
        }

        $xml = '<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
                    <d:prop>
                        <d:getetag />
                    </d:prop>'
            . $filter_xml .
            '</c:calendar-query>';

        $res = $this->curl->report($url, $xml, array(
            CURLOPT_HTTPHEADER => array(
                'Depth: ' . $depth
            )
        ));
        $this->checkError($res);
        $message = $res['body']['message'];
        return new SimpleXMLElement($message);
    }

    public function reportCalendarChanges($url = '', $filter = array(), $depth = 0)
    {
        $url = $this->buildUrl($url);

        $filter_xml = '';
        if ($filter) {
            $filter_xml = '<c:filter>
                <c:comp-filter name="VCALENDAR">
                    <c:comp-filter name="VEVENT">
                        :time_range
                    </c:comp-filter>
                </c:comp-filter>
            </c:filter>';
            foreach (array('time_range') as $var) {
                $node = '';
                if (isset($filter[$var]) && is_array($filter[$var])) {
                    $attributes = array();
                    foreach ($filter[$var] as $attr_name => $attr_value) {
                        $attributes[] = "{$attr_name}=\"{$attr_value}\"";
                    }
                    $tag = str_replace('_', '-', $var);
                    $node = '<c:' . $tag . ' ' . join(' ', $attributes) . ' />';
                }
                $filter_xml = str_replace(':' . $var, $node, $filter_xml);
            }
        }

        $xml = '<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
                    <d:prop>
                        <d:getetag />
                        <c:calendar-data />
                    </d:prop>'
            . $filter_xml .
            '</c:calendar-query>';

        $res = $this->curl->report($url, $xml, array(
            CURLOPT_HTTPHEADER => array(
                'Depth: ' . $depth
            )
        ));
        $this->checkError($res);
        $message = $res['body']['message'];
        return new SimpleXMLElement($message);
    }

    public function propFind($url, $properties = array(), $nss = array())
    {
        $url = $this->buildUrl($url);

        $nss = array('d' => 'DAV:') + $nss;
        $nss_str = array();
        foreach ($nss as $ns_key => $ns_value) {
            $nss_str[] = 'xmlns:' . $ns_key . '="'.$ns_value.'"';
        }
        $nss_str = join(" ", $nss_str);

        $xml = '<d:propfind ' . $nss_str . '>';
        $xml .= '<d:prop>';
        foreach ($properties as $property) {
            $xml .= '<' . $property . ' />';
        }
        $xml .= '</d:prop>';
        $xml .= '</d:propfind>';

        $res = $this->curl->propfind($url, $xml);
        $this->checkError($res);
        $message = $res['body']['message'];
        return new SimpleXMLElement($message);
    }

    public function addEvent($url, $event, $options = array())
    {
        $url = $this->buildUrl($url);

        $auth = base64_encode(join(':', array($this->login, $this->password)));

        $headers = array(
            'User-Agent: ' . $this->user_agent,
            'Authorization: Basic ' . $auth,
            'Content-Type: text/calendar; charset=utf-8',
        );

        // fruux failed when use content-length header
        if (strpos($this->url, 'dav.fruux.com') === false) {
            $headers[] = 'Content-Length: ' . strlen($event);
        }

        /*if (!empty($options['etag'])) {
            $headers[] = 'If-Match: ' . $options['etag'];
        }*/

        $context_options = array(
            'http' => array(
                'method' => 'PUT',
                'header' => join("\n", $headers),
                'content' => $event
            )
        );

        $context = stream_context_create($context_options);

        $stream = @fopen($url, 'rb', false, $context);

        $http_headers = array();
        if (isset($http_response_header)) {
            $http_headers = (array) $http_response_header;
        }

        $http_status = 0;
        $first_response_header = ifset($http_headers[0], '');
        if (preg_match("!http/1\.[01]\s([1-5][0-9][0-9])\s(.*)!i", $first_response_header, $m)) {
            $http_status = $m[1];
        }

        $result = '';
        if ($stream) {
            $result = stream_get_contents($stream);
            fclose($stream);
        }

        $response = array(
            'http_code' => $http_status,
            'body' => array(
                'message' => $result
            )
        );

        $this->checkError($response);

        return $result;
    }

    public function updateEvent($url, $event, $options = array())
    {
        $url = $this->buildUrl($url);

        $auth = base64_encode(join(':', array($this->login, $this->password)));

        $headers = array(
            'User-Agent: ' . $this->user_agent,
            'Authorization: Basic ' . $auth,
            'Content-Type: text/calendar; charset=utf-8',
        );

        // fruux failed when use content-length header
        if (strpos($this->url, 'dav.fruux.com') === false) {
            $headers[] = 'Content-Length: ' . strlen($event);
        }

        /*if (!empty($options['etag'])) {
            $headers[] = 'If-Match: ' . $options['etag'];
        }*/

        $context_options = array(
            'http' => array(
                'method' => 'PUT',
                'header' => join("\n", $headers),
                'content' => $event
            )
        );

        $context = stream_context_create($context_options);

        $stream = @fopen($url, 'rb', false, $context);

        $http_headers = array();
        if (isset($http_response_header)) {
            $http_headers = (array) $http_response_header;
        }

        $http_status = 0;
        $first_response_header = ifset($http_headers[0], '');
        if (preg_match("!http/1\.[01]\s([1-5][0-9][0-9])\s(.*)!i", $first_response_header, $m)) {
            $http_status = $m[1];
        }

        $result = '';
        if ($stream) {
            $result = stream_get_contents($stream);
            fclose($stream);
        }

        $response = array(
            'http_code' => $http_status,
            'body' => array(
                'message' => $result
            )
        );

        $this->checkError($response);

        return $result;
    }

    public function deleteEvent($url, $options = array())
    {
        $url = $this->buildUrl($url);

        $auth = base64_encode(join(':', array($this->login, $this->password)));

        $headers = array(
            'User-Agent: ' . $this->user_agent,
            'Authorization: Basic ' . $auth
        );

        $context_options = array(
            'http' => array(
                'method' => 'DELETE',
                'header' => join("\n", $headers)
            )
        );

        $context = stream_context_create($context_options);

        $stream = @fopen($url, 'rb', false, $context);

        $http_headers = array();
        if (isset($http_response_header)) {
            $http_headers = (array) $http_response_header;
        }

        $http_status = 0;
        $first_response_header = ifset($http_headers[0], '');
        if (preg_match("!http/1\.[01]\s([1-5][0-9][0-9])\s(.*)!i", $first_response_header, $m)) {
            $http_status = $m[1];
        }

        $result = '';
        if ($stream) {
            $result = stream_get_contents($stream);
            fclose($stream);
        }

        $response = array(
            'http_code' => $http_status,
            'body' => array(
                'message' => $result
            )
        );

        try {
            $this->checkError($response);
        } catch (teamCaldavClientException $e) {
            if ($e->getCode() == 404) {
                throw new teamCalendarExternalEventNotFoundException();
            }
        }

        return $result;
    }

    public function buildUrl($url)
    {
        if (strpos($this->url, 'caldav.calendar.yahoo.com') !== false) {
            $url = str_replace(' ', '%20', $url);
        }
        if (strpos($url, '-caldav.icloud.com') === false) {
            $url = $this->url . ltrim($url, '/');
        }
        return $url;
    }

    private function checkError($curl_response, $check_body_message = true)
    {
        $http_code = ifset($curl_response['http_code'], 0);

        if (!$http_code) {
            throw new teamCaldavClientException("Can't reach server");
        }
        if ($http_code == 401) {
            throw new teamCaldavClientException('Unauthorisized', 401);
        }
        if ($http_code == 403) {
            throw new teamCaldavClientException("Forbidden request", 403);
        }
        if ($curl_response['http_code'] == 404) {
            throw new teamCaldavClientException("Request url not found", 404);
        }
        if ($http_code >= 500) {
            throw new teamCaldavClientException("CalDAV server error", 500);
        }

        if ($check_body_message) {
            if (empty($curl_response['body']) && empty($curl_response['body']['message'])) {
                throw new teamCaldavClientException("Response message is empty");
            }
        }
    }
}
