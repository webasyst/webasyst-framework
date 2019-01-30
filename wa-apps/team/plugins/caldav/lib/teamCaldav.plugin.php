<?php

class teamCaldavPlugin extends teamCalendarExternalPlugin
{
    public function authorizeBegin($id, $options = array())
    {
        $template_path = $this->getTemplatePath('authorize.html');
        return $this->renderTemplate($template_path, array(
            'id' => $id,
            'url' => self::getCallbackUrlById('caldav')
        ));
    }

    public function authorizeEnd($options = array())
    {
        $post = wa()->getRequest()->post();
        $id = (int) ifset($post['id']);
        $url = (string) ifset($post['url']);
        $login = (string) ifset($post['login']);
        $password = (string) ifset($post['password']);

        try {
            $res = $this->authorize($url, $login, $password);
            $res['id'] = $id;
            return $res;
        } catch (teamCaldavClientException $e) {
            $e = new teamCalendarExternalAuthorizeFailedException($e->getMessage());
            $e->setParams(array(
                'id' => $id
            ));
            throw $e;
        }
    }

    public function authorize($url, $login, $password)
    {
        $client = new teamCaldavClient($url, $login, $password);
        $client->checkConnection();

        $user_principal_url = $this->getUserPrincipalUrl(array(
            'url' => $url,
            'login' => $login,
            'password' => $password
        ));
        $calendars_url = $this->getCalendarsUrl(array(
            'url' => $url,
            'login' => $login,
            'password' => $password,
            'user_principal_url' => $user_principal_url
        ));
        return array(
            'url' => $url,
            'login' => $login,
            'is_connected' => 1,
            'password' => $password,
            'user_principal_url' => $user_principal_url,
            'calendars_url' => $calendars_url
        );
    }

    /**
     * @param array $options
     * @return array
     */
    public function getCalendars($options = array())
    {
        if (!$this->checkCalendar()) {
            return array();
        }
        $params = $this->calendar->getParams();
        $params = array_merge($params, $options);
        $client = new teamCaldavClient($params['url'], $params['login'], $params['password']);

        if (empty($params['calendars_url'])) {
            $calendars_url = $this->getCalendarsUrl($params);
        } else {
            $calendars_url = $params['calendars_url'];
        }

        $xml = $client->allProp($calendars_url, 1);
        $this->logXml($xml, __METHOD__ . ' Try # 1');

        $calendars = $this->getCalendarsFromXml($xml);

        if (empty($calendars)) {
            $xml = $client->propFind($calendars_url,
                array('d:displayname ', 'cs:getctag', 'd:resourcetype', 'd:href'),
                array('d' => "DAV:", 'cs' => 'http://calendarserver.org/ns/')
            );
            $this->logXml($xml, __METHOD__ . ' Try # 2');

            $calendars = $this->getCalendarsFromXml($xml);
        }

        return $calendars;
    }

    protected function getCalendarsFromXml($xml)
    {
        $xml->registerXPathNamespace('D', 'DAV:');
        $xml->registerXPathNamespace('cs', 'http://calendarserver.org/ns/');
        $responses = $xml->xpath('//D:response');

        $calendars = array();

        foreach ($responses as $response) {
            $href = '';
            $propstat = null;
            foreach ($response->xpath('child::node()') as $child) {
                $name = $child->getName();
                if ($name === 'href') {
                    $href = (string) $child;
                } else if ($name === 'propstat') {
                    $propstat = $child;
                }
                if ($href && ($propstat instanceof SimpleXMLElement)) {
                    break;
                }
            }

            if ($href && ($propstat instanceof SimpleXMLElement)) {

                $prop = null;
                foreach ($propstat->xpath('child::node()') as $child) {
                    if ($child->getName() === 'prop') {
                        $prop = $child;
                        break;
                    }
                }

                if ($prop instanceof SimpleXMLElement) {

                    $prop->registerXPathNamespace('d', 'DAV:');
                    $prop->registerXPathNamespace('cs', 'http://calendarserver.org/ns/');

                    $rt = $prop->xpath('d:resourcetype');
                    if (empty($rt)) {
                        continue;
                    }
                    $rt = $rt[0];

                    $rt->registerXPathNamespace('c', 'urn:ietf:params:xml:ns:caldav');
                    $cal_ar = $rt->xpath('c:calendar');
                    if (empty($cal_ar)) {
                        continue;
                    }

                    $getctag = $prop->xpath('cs:getctag');

                    if (empty($getctag)) {
                        // so try getetag. For example yahoo.com has getetag, but doesn't getctag
                        $getctag = $prop->xpath('d:getetag');
                        if (empty($getctag)) {
                            continue;
                        }
                    }

                    $getctag = (string) $getctag[0];
                    if (!$getctag) {
                        continue;
                    }

                    $displayname = $prop->xpath('d:displayname');
                    if (empty($displayname)) {
                        $displayname = '';
                    } else {
                        $displayname = (string) $displayname[0];
                    }

                    $calendars[] = array(
                        'id' => urldecode($href),
                        'ctag' => $getctag,
                        'name' => $displayname,
                    );

                }
            }
        }

        return $calendars;
    }

    protected function getUserPrincipalUrl($params)
    {
        $client = new teamCaldavClient($params['url'], $params['login'], $params['password']);

        $xml = $client->allProp();
        $this->logXml($xml, __METHOD__ . ' Try # 1');
        $principal_url = $this->getUserPrincipalUrlFromXml($xml);
        if (!$principal_url) {
            // try more specific request
            $xml = $client->propFind('/',
                array('D:current-user-principal'),
                array('D' => 'DAV:')
            );
            $this->logXml($xml, __METHOD__ . ' Try # 2');
            $principal_url = $this->getUserPrincipalUrlFromXml($xml);
        }

        return $principal_url;
    }

    protected function getUserPrincipalUrlFromXml($xml)
    {
        $xml->registerXPathNamespace('D', 'DAV:');
        $xpath = "//D:current-user-principal/D:href";
        $res = $xml->xpath($xpath);
        if (!is_array($res) || count($res) <= 0) {
            return '';
        }
        if (!($res[0] instanceof SimpleXMLElement)) {
            return '';
        }
        $principal_url = (string) $res[0];
        return urldecode($principal_url);
    }

    protected function getCalendarsUrl($params)
    {
        $client = new teamCaldavClient($params['url'], $params['login'], $params['password']);

        if (empty($params['user_principal_url'])) {
            $user_principal_url = $this->getUserPrincipalUrl($params);
        } else {
            $user_principal_url = $params['user_principal_url'];
        }

        $xml = $client->propFind($user_principal_url,
            array('C:calendar-home-set'),
            array('C' => 'urn:ietf:params:xml:ns:caldav')
        );

        $this->logXml($xml, __METHOD__);
        $xml->registerXPathNamespace('D', 'DAV:');
        $xml->registerXPathNamespace('ietf', 'urn:ietf:params:xml:ns:caldav');
        $xpath = "//ietf:calendar-home-set/D:href";
        $res = $xml->xpath($xpath);
        if (!is_array($res) || count($res) <= 0) {
            return '';
        }
        if (!($res[0] instanceof SimpleXMLElement)) {
            return '';
        }
        return urldecode((string) $res[0]);
    }

    public function getEvents($options = array())
    {
        if (!$this->checkCalendar()) {
            return array();
        }
        if (!$this->isConnected()) {
            return array();
        }
        $url = $this->calendar->getNativeCalendarId();
        if (!$url) {
            return array();
        }

        $params = $this->calendar->getParams();
        $params = array_merge($params, $options);

        $client = new teamCaldavClient($params['url'], $params['login'], $params['password']);

        $range = $this->calculateTimeRange($params);
        if ($range['end'] <= $range['start']) {
            return array(
                'info' => array(
                    'done' => true,
                    'imported' => true,
                ),
                'list' => array()
            );
        }

        $filter = array(
            'time_range' => array(
                'start' => date('Ymd\THis\Z', $range['start']),
                'end' => date('Ymd\THis\Z', $range['end'])
            )
        );

        $xml = $client->reportCalendarData($url, $filter, 1);
        $this->logXml($xml, __METHOD__);

        $xml->registerXPathNamespace('D', 'DAV:');
        $data = $this->getEventsFields($xml);

        // some servers (ie iCloud) doesn't return calendar data in report request
        // so extract hrefs and try multi-get request
        $multiget = array();
        foreach ($data as $index => $item) {
            if (empty($item['calendar_data'])) {
                if (!empty($item['href'])) {
                    $multiget[] = $item['href'];
                }
                unset($data[$index]);
            }
        }

        if ($multiget) {
            $xml = $client->multigetCalendarData($url, $multiget);
            $xml->registerXPathNamespace('D', 'DAV:');
            foreach ($this->getEventsFields($xml) as $item) {
                $data[] = $item;
            }
        }

        $events = array();
        foreach ($data as $item) {
            $event_data = $this->extractEventData($item['calendar_data']);
            $vevent = new teamIcalEvent($event_data);
            $event = $vevent->toAppEvent();
            $event['etag'] = $item['etag'];
            $event['href'] = $item['href'];
            $end = strtotime($event['end']);
            if ($range['start'] < $end) {
                $events[] = $event;
            }
        }

        $done = (bool) ifset($params['done']);

        $count = count($events);
        $empty_times = (int) ifset($params['empty_times'], 0);
        if ($count <= 0) {
            $empty_times += 1;
        } else {
            $empty_times = 0;
        }

        $done = $done || $empty_times >= 3;

        $info = array(
            'done' => $done,
            'imported' => $done,
            'empty_times' => null,
            'count' => null,
            'start' => null
        );
        if (!$done) {
            $info = array_merge($info, array(
                'empty_times' => $empty_times,
                'count' => $count,
                'start' => date('Y-m-d', $range['end'])
            ));
        }

        return array(
            'info' => $info,
            'list' => $events
        );
    }

    protected function calculateTimeRange($params, $for_change = false)
    {
        $start_datetime = date('Y-m-d');

        if ($for_change) {
            if (isset($params['min_time'])) {
                $start_datetime = date('Y-m-d', strtotime($params['min_time']));
            }
        } else {
            if (isset($params['start'])) {
                $start_datetime = date('Y-m-d', strtotime($params['start']));
            } else if (isset($params['min_time'])) {
                $start_datetime = date('Y-m-d', strtotime($params['min_time']));
            }
        }

        $start_timestamp = strtotime($start_datetime);
        $end_timestamp = strtotime('+3 month', $start_timestamp);

        $max_time = ifset($params['max_time']);
        $max_timestamp = $max_time !== null ? strtotime(date('Y-m-d', strtotime($max_time))) : null;
        if ($max_timestamp !== null) {
            if ($for_change) {
                $end_timestamp = $max_timestamp;
            } else {
                $end_timestamp = min($max_timestamp, $end_timestamp);
            }
        }

        return array(
            'start' => $start_timestamp,
            'end' => $end_timestamp
        );
    }

    public function isImported()
    {
        if (!$this->isMapped()) {
            return false;
        }
        return (bool) $this->calendar->getParam('imported');
    }

    public function getChanges($options = array())
    {
        if (!$this->checkCalendar()) {
            return array();
        }
        if (!$this->isConnected()) {
            return array();
        }
        $url = $this->calendar->getNativeCalendarId();
        if (!$url) {
            return array();
        }

        $result = $this->findWhichEventsChanged($options);
        if (empty($result['change']) && empty($result['delete'])) {
            return array();
        }

        $params = $this->calendar->getParams();
        $params = array_merge($params, $options);

        $client = new teamCaldavClient($params['url'], $params['login'], $params['password']);

        $xml = $client->multigetCalendarData($url, $result['change']);

        $data = $this->getEventsFields($xml);
        $events = array();

        $range = $this->calculateTimeRange($params, true);

        foreach ($data as $item) {
            $event_data = $this->extractEventData($item['calendar_data']);
            $vevent = new teamIcalEvent($event_data);
            $event = $vevent->toAppEvent();
            $event['etag'] = $item['etag'];
            $event['href'] = $item['href'];
            $end = strtotime($event['end']);
            if ($range['start'] < $end) {
                $events[] = $event;
            }
        }

        $delete = array();
        if (!empty($result['delete'])) {
            $eem = new teamEventExternalModel();
            $delete = $eem->getNativeEventIdsByCalendarAndParam($this->calendar->getId(), 'href', $result['delete']);
        }

        return array(
            'change' => $events,
            'delete' => $delete,
            'info' => ifset($result['info'], array())
        );

    }

    protected function findWhichEventsChanged($options = array())
    {
        if (!$this->isConnected()) {
            return array();
        }
        $url = $this->calendar->getNativeCalendarId();
        if (!$url) {
            return array();
        }

        $ctag = $this->isCalendarChanged();
        if (!$ctag) {
            return array();
        }

        $params = $this->calendar->getParams();
        $params = array_merge($params, $options);

        $client = new teamCaldavClient($params['url'], $params['login'], $params['password']);

        $range = $this->calculateTimeRange($params, true);

        if ($range['end'] <= $range['start']) {
            return array(
                'change' => array(),
                'delete' => array(),
                'info' => array(
                    'ctag' => $ctag
                )
            );
        }

        $filter = array(
            'time_range' => array(
                'start' => date('Ymd\THis\Z', $range['start']),
                'end' => date('Ymd\THis\Z', $range['end'])
            )
        );

        $xml = $client->reportCalendarChangedTag($url, $filter, 1);
        $changed_events_map = array();
        foreach ($this->getEventsFields($xml, array('href', 'etag')) as $event) {
            $changed_events_map[$event['href']] = $event;
        }

        $m = new waModel();

        $sql = "SELECT eep_href.value AS href, eep_etag.value AS etag, ee.id
                  FROM `team_calendar_external` ce 
                    JOIN `team_event_external` ee ON ce.id = ee.calendar_external_id
                    LEFT JOIN `team_event_external_params` eep_href ON eep_href.event_external_id = ee.id AND eep_href.name = 'href'
                    LEFT JOIN `team_event_external_params` eep_etag ON eep_etag.event_external_id = ee.id AND eep_etag.name = 'etag'
                  WHERE ce.id = ?";

        $old_events_map = $m->query($sql, $this->calendar->getId())->fetchAll('href');

        $change = array();
        foreach ($changed_events_map as $event) {
            if (!isset($old_events_map[$event['href']]) || $old_events_map[$event['href']]['etag'] != $event['etag']) {
                $change[] = $event['href'];
            }
        }

        $delete = array();
        foreach ($old_events_map as $event) {
            if (!isset($changed_events_map[$event['href']])) {
                $delete[] = $event['href'];
            }
        }

        return array(
            'change' => $change,
            'delete' => $delete,
            'info' => array(
                'ctag' => $ctag
            )
        );
    }

    /**
     * @return bool|string
     */
    protected function isCalendarChanged()
    {
        if (!$this->isConnected()) {
            return array();
        }
        $url = $this->calendar->getNativeCalendarId();
        if (!$url) {
            return array();
        }

        $params = $this->calendar->getParams();
        $ctag = ifset($params['ctag'], '');

        $calendars = $this->getCalendars();
        foreach ($calendars as $calendar) {
            if ($calendar['id'] == $url && $calendar['ctag'] != $ctag) {
                return $calendar['ctag'];
            }
        }

        return false;
    }

    public function isConnected()
    {
        return $this->calendar->getParam('is_connected');
    }

    /**
     * Pretty print xml for debug reasons
     * TODO: delete later
     * @param $xml
     * @param null|string $filepath
     * @param string $tag
     */
    protected static function ppXml($xml, $filepath = null, $tag = '')
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML(($xml instanceof SimpleXMLElement) ? $xml->asXML() : $xml);
        $out = $dom->saveXML();
        if ($filepath) {
            if ($tag) {
                waLog::log($tag, $filepath);
            }
            waLog::log($out, $filepath);
        } else {
            echo htmlspecialchars($out);
        }
    }

    private function logXml($xml, $tag = '')
    {
        if (waSystemConfig::isDebug()) {
            $tag = $this->getCalendarName() . ' - ' . $tag;
            self::ppXml($xml, 'team/caldav/debug.log', $tag);
        }
    }

    protected function getTemplatePath($path)
    {
        return $this->path . '/templates/' . $path;
    }

    protected function renderTemplate($template_path, $assign = array())
    {
        waSystem::pushActivePlugin('caldav', 'team');
        $view = wa()->getView();
        $vars = $view->getVars();
        $view->clearAllAssign();
        $view->assign($assign);
        $res = $view->fetch($template_path);
        $view->clearAllAssign();
        $view->assign($vars);
        waSystem::popActivePlugin();
        return $res;
    }

    public function typecastCalendarExternal($calendar_external)
    {
        $calendar_external = parent::typecastCalendarExternal($calendar_external);
        foreach (array('url', 'login', 'password') as $field) {
            $calendar_external['params'][$field] = isset($calendar_external['params'][$field]) ? $calendar_external['params'][$field] : '';
        }
        return $calendar_external;
    }

    private function extractEventData($calendar_data)
    {
        $begin = strpos($calendar_data, 'BEGIN:VEVENT') + 12;   // length of 'BEGIN:VEVENT' string
        $end = strpos($calendar_data, 'END:VEVENT', $begin);
        return substr($calendar_data, $begin, $end - $begin);
    }

    private function getEventsFields($xml, $fields = array('href', 'etag', 'calendar_data', 'getcontenttype'))
    {
        $xml->registerXPathNamespace('D', 'DAV:');
        $responses = $xml->xpath('//D:response');

        $fields_map = array_fill_keys($fields, true);

        $data = array();

        foreach ($responses as $response) {

            $select = array('href' => '', 'etag' => '', 'calendar_data' => '', 'getcontenttype' => '');

            $response->registerXPathNamespace('D', 'DAV:');
            $href = $response->xpath('D:href');
            if ($href) {
                $select['href'] = urldecode((string) $href[0]);
            }

            $response->registerXPathNamespace('D', 'DAV:');
            $propstat = $response->xpath('D:propstat');
            if (!$propstat) {
                continue;
            }
            $propstat = $propstat[0];

            $propstat->registerXPathNamespace('D', 'DAV:');
            $prop = $propstat->xpath('D:prop');
            if (!$prop) {
                continue;
            }
            $prop = $prop[0];

            $prop->registerXPathNamespace('D', 'DAV:');
            $etag = $prop->xpath('D:getetag');
            if ($etag) {
                $select['etag'] = (string) $etag[0];
            }

            $prop->registerXPathNamespace('C', 'urn:ietf:params:xml:ns:caldav');
            $calendar_data = $prop->xpath('C:calendar-data');
            if ($calendar_data) {
                $select['calendar_data'] = (string) $calendar_data[0];
            }

            $getcontenttype = $prop->xpath('D:getcontenttype');
            if ($getcontenttype) {
                $select['getcontenttype'] = (string) $getcontenttype[0];
            }

            foreach ($select as $fld => $val) {
                if (!isset($fields_map[$fld])) {
                    unset($select[$fld]);
                }
            }

            $data[] = $select;
        }

        return $data;
    }

    /**
     * @param array $options
     * @return string
     */
    public function getAccountInfoHtml($options = array())
    {
        $login = $this->calendar->getParam('login');
        $url_and_login = $this->calendar->getParam('url') . '@' . $login;
        $provider_name = $this->getProvider();
        if (strlen($provider_name) <= 0) {
            return $url_and_login;
        }
        return "{$provider_name}: {$login}";
    }

    public function addEvent($event, $options = array())
    {
        if (!$this->checkCalendar()) {
            return false;
        }

        if (!$this->calendar->getNativeCalendarId()) {
            return false;
        }

        $vevent = teamIcalEvent::parseAppEvent($event);
        $vevent = join("\n", array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            $vevent,
            'END:VCALENDAR'
        ));
        $options = $this->calendar->getParams() + $options;
        $client = new teamCaldavClient($options['url'], $options['login'], $options['password']);
        $url = rtrim($this->calendar->getNativeCalendarId(), '/') . '/' . $event['uid'] . '.ics';
        $client->addEvent($url, $vevent);

        $xml = $client->multigetCalendarData($this->calendar->getNativeCalendarId(), array($url));
        $this->logXml($xml, __METHOD__);
        $data = $this->getEventsFields($xml);

        $event['native_event_id'] = $event['uid'];

        $event['params'] = ifset($event['params'], array());
        $item = reset($data);
        $event['params']['href'] = $item['href'];
        $event['params']['etag'] = $item['etag'];

        return $event;
    }

    /**
     * @param $event
     * @param array $options
     * @return bool|array
     */
    public function updateEvent($event, $options = array())
    {
        if (!$this->checkCalendar()) {
            return false;
        }

        if (!$this->calendar->getNativeCalendarId()) {
            return false;
        }

        $event_params = ifset($event['params'], array());
        $url = ifset($event_params['href']);
        if (!$url) {
            return false;
        }

        $vevent = teamIcalEvent::parseAppEvent($event);
        $vevent = join("\n", array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            $vevent,
            'END:VCALENDAR'
        ));
        $options = $this->calendar->getParams() + $options;
        $client = new teamCaldavClient($options['url'], $options['login'], $options['password']);

        if (!$this->getEvent($url)) {
            throw new teamCalendarExternalEventNotFoundException();
        }

        $client->updateEvent($url, $vevent, $event_params);

        $xml = $client->multigetCalendarData($this->calendar->getNativeCalendarId(), array($url));
        $this->logXml($xml, __METHOD__);
        $data = $this->getEventsFields($xml);

        foreach ($data as $item) {
            if ($item['href'] == $event['params']['href'] && $item['etag'] != $event['params']['etag']) {
                $event['params']['etag'] = $item['etag'];
                return $event;
            }
        }

        return true;
    }

    public function deleteEvent($event, $options = array())
    {
        if (!$this->checkCalendar()) {
            return false;
        }

        if (!$this->calendar->getNativeCalendarId()) {
            return false;
        }
        
        $event_params = ifset($event['params'], array());
        $url = ifset($event_params['href']);
        if (!$url) {
            return false;
        }

        $options = $this->calendar->getParams() + $options;
        $client = new teamCaldavClient($options['url'], $options['login'], $options['password']);

        if (!$this->getEvent($url)) {
            throw new teamCalendarExternalEventNotFoundException();
        }

        $client->deleteEvent($url, $event_params);

        return true;
    }

    protected function getEvent($event_url)
    {
        if (!$this->checkCalendar()) {
            return false;
        }

        $calendar_url = $this->calendar->getNativeCalendarId();
        if (!$calendar_url) {
            return false;
        }

        $params = $this->calendar->getParams();
        $client = new teamCaldavClient($params['url'], $params['login'], $params['password']);
        $urls = array(
            $event_url
        );

        $data = array();
        $xml = $client->multigetCalendarData($calendar_url, $urls);
        $xml->registerXPathNamespace('D', 'DAV:');
        foreach ($this->getEventsFields($xml) as $item) {
            $data[] = $item;
        }

        if (empty($data)) {
            return null;
        }

        $events = array();
        foreach ($data as $item) {
            $event_data = $this->extractEventData($item['calendar_data']);
            $vevent = new teamIcalEvent($event_data);
            $event = $vevent->toAppEvent();
            $event['etag'] = $item['etag'];
            $event['href'] = $item['href'];
            $events[] = $event;
        }

        return $events[0];
    }

    public function getCalendarName()
    {
        $calendar_name = parent::getCalendarName();
        $provider_name = $this->getProvider();
        if (strlen($calendar_name) <= 0) {
            return $provider_name;
        }
        if (strlen($provider_name) <= 0) {
            return $calendar_name;
        }
        return "{$calendar_name} ({$provider_name})";
    }

    public function getProvider()
    {
        if (!$this->checkCalendar()) {
            return '';
        }
        $url = $this->calendar->getParam('url');
        if (strpos($url, '.yandex.ru') !== false) {
            return 'Yandex';
        }
        if (strpos($url, '.yahoo.com') !== false) {
            return 'Yahoo!';
        }
        if (strpos($url, '.fruux.com') !== false) {
            return 'Fruux';
        }
        if (strpos($url, '-caldav.icloud.com') !== false) {
            return 'ICloud';
        }
        return '';
    }
}
