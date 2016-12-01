<?php

/**
 * Class teamOffice365Plugin
 * @property-read $client_id
 * @property-read $client_secret
 */
class teamOffice365Plugin extends teamCalendarExternalPlugin
{
    private static $plugin_id = 'office365';

    const DATE_TIME = 'Y-m-d H:i:s';
    const DATE_YMD = 'Y-m-d';
    const DATE_ISO8601 = 'c';

    /**
     * @var teamOffice365Oauth
     */
    protected $oauth;

    /**
     * @param $id
     * @param array $options
     * @return string
     * @throws teamCalendarExternalAuthorizeFailedException
     */
    public function authorizeBegin($id, $options = array())
    {
        try {
            return $this->getOauth()->authorizeBegin($id);
        } catch (teamGooglecalendarOauthException $e) {
            $e = new teamCalendarExternalAuthorizeFailedException($e->getMessage());
            $e->setParams(array(
                'id' => $id
            ));
            throw $e;
        }
    }

    /**
     * @param array $options
     * @return array
     * @throws teamCalendarExternalAuthorizeFailedException
     */
    public function authorizeEnd($options = array())
    {
        try {
            $res = $this->getOauth()->authorizeEnd($options);
        } catch (teamOffice365OauthException $e) {
            $msg = $e->getMessage();
            $params = $e->getParams();
            $e = new teamCalendarExternalAuthorizeFailedException($msg);
            $e->setParams(array(
                'id' => $params['id']
            ));
            throw $e;
        }

        $res['token_invalid'] = null;

        $info = $this->getOauth()->getUserInfo($res['token']);
        foreach (array('email' => 'mail', 'id' => 'id', 'name' => 'displayName') as $field_to => $field_from) {
            if (isset($info[$field_from])) {
                $res['user_' . $field_to] = $info[$field_from];
            }
        }

        $user_id = isset($res['user_id']) ? $res['user_id'] : null;

        $calendar = new teamCalendarExternal($res['id']);
        if ($calendar->getParam('user_id') && $calendar->getParam('user_id') != $user_id) {
            $e = new teamCalendarExternalAuthorizeFailedException(_wp('Another Microsoft account'));
            $e->setParams(array(
                'id' => $calendar->getId()
            ));
            throw $e;
        }

        return $res;
    }

    /**
     * @throws teamCalendarExternalTokenInvalidException
     * @param array $options
     * @return array
     */
    public function getCalendars($options = array())
    {
        if (!$this->checkCalendar()) {
            return array();
        }

        $this->checkTokenInvalid();

        $driver = $this->getDriver($this->calendar->getParam('token'), $this->calendar->getParams());

        $res = array();
        try {
            $res = $driver->getCalendars();
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException($e);
        }

        $this->debugLog($res, __METHOD__);
        $calendars = array();
        foreach (ifset($res['value'], array()) as $calendar) {
            $name = $calendar['name'];
            $id = $calendar['id'];
            $calendars[] = array(
                'id' => $id,
                'name' => $name
            );
        }
        return $calendars;
    }

    /**
     * @param array $options
     * @return array|false
     */
    public function getEvents($options = array())
    {
        if (!$this->checkCalendar()) {
            return array();
        }

        $this->checkTokenInvalid();

        $params = $this->calendar->getParams();
        $options = array_merge($params, $options);

        $driver = $this->getDriver($params['token'], $options);

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

        $options['startDateTime'] = date(self::DATE_ISO8601, $range['start']);
        $options['endDateTime'] = date(self::DATE_ISO8601, $range['end']);

        try {
            $res = $driver->getEvents($this->calendar->getNativeCalendarId(), $options);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException($e);
        }

        $this->debugLog($res, __METHOD__);

        $events = array();
        foreach (ifset($res['value'], array()) as $item) {
            $event = $this->listItemToEvent($item);
            $events[] = $event;
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

    /**
     * @return bool
     */
    public function isImported()
    {
        if (!$this->isMapped()) {
            return false;
        }
        return (bool)$this->calendar->getParam('imported');
    }

    /**
     * @param array $options
     * @return array|false
     */
    public function getChanges($options = array())
    {
        if (!$this->getCalendar()) {
            return array();
        }
        if (!$this->isConnected()) {
            return array();
        }
        $url = $this->calendar->getNativeCalendarId();
        if (!$url) {
            return array();
        }

        $this->checkTokenInvalid();

        $params = $this->calendar->getParams();
        $params = array_merge($params, $options);

        $range = $this->calculateTimeRange($params, true);

        if ($range['end'] <= $range['start']) {
            return array(
                'change' => array(),
                'delete' => array()
            );
        }

        $driver = $this->getDriver($params['token'], $params);

        $options['startDateTime'] = date(self::DATE_ISO8601, $range['start']);
        $options['endDateTime'] = date(self::DATE_ISO8601, $range['end']);

        try {
            $res = $driver->getEvents($this->calendar->getNativeCalendarId(), $options);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException($e);
        }

        $this->debugLog($res, __METHOD__);

        $changed_events_map = array();
        foreach (ifset($res['value']) as $item) {
            $event = $this->listItemToEvent($item);
            $changed_events_map[$event['native_event_id']] = $event;
        }

        $m = new waModel();

        $sql = "SELECT eep_ck.value AS changeKey, ee.native_event_id
                  FROM `team_calendar_external` ce 
                    JOIN `team_event_external` ee ON ce.id = ee.calendar_external_id
                    LEFT JOIN `team_event_external_params` eep_ck ON eep_ck.event_external_id = ee.id AND eep_ck.name = 'changeKey'
                  WHERE ce.id = ?";

        $old_events_map = $m->query($sql, $this->calendar->getId())->fetchAll('native_event_id');

        $change = array();
        foreach ($changed_events_map as $event) {
            if (!isset($old_events_map[$event['native_event_id']]) || $old_events_map[$event['native_event_id']]['changeKey'] != $event['changeKey']) {
                $change[] = $event;
            }
        }

        $delete = array();
        foreach ($old_events_map as $event) {
            if (!isset($changed_events_map[$event['native_event_id']])) {
                $delete[] = $event['native_event_id'];
            }
        }

        return array(
            'change' => $change,
            'delete' => $delete,
        );
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        if (!$this->checkCalendar()) {
            return false;
        }
        return !!$this->calendar->getParam('user_id');
    }
    /**
     * @param array $options
     * @return string
     */
    public function getAccountInfoHtml($options = array())
    {
        $template_path = $this->getTemplatePath('accountInfo.html');
        if (!$this->checkCalendar()) {
            return _wp('Unknown calendar');
        }
        return $this->renderTemplate($template_path, array(
            'calendar' => array(
                'id' => $this->calendar->getId(),
                'is_own' => $this->calendar->getContactId() == wa()->getUser()->getId(),
                'params' => array(
                    'user_name' => $this->calendar->getParam('user_name'),
                    'user_email' => $this->calendar->getParam('user_email')
                ),
            ),
            'is_token_invalid' => $this->isTokenInvalid(),
            'action' => ifset($options['action'])
        ));
    }

    public function addEvent($event, $options = array())
    {
        if (!$this->checkCalendar()) {
            return false;
        }
        $native_calendar_id = $this->calendar->getNativeCalendarId();
        if (!$native_calendar_id) {
            return false;
        }

        $this->checkTokenInvalid();

        $office_event = $this->appEventToOfficeEvent($event);

        $options = $this->calendar->getParams() + $options;
        $res = array();
        try {
            $driver = $this->getDriver($options['token'], $options);
            $res = $driver->addEvent($native_calendar_id, $office_event);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException($e);
        }

        if ($res) {
            $event = array_merge($event, $this->listItemToEvent($res));
            return $event;
        }

        return true;
    }

    /**
     * @throws teamCalendarExternalTokenInvalidException
     * @param $event
     * @param array $options
     * @return bool|array
     */
    public function updateEvent($event, $options = array())
    {
        if (!$this->checkCalendar()) {
            return false;
        }
        $native_calendar_id = $this->calendar->getNativeCalendarId();
        if (!$native_calendar_id) {
            return false;
        }
        if (empty($event['native_event_id'])) {
            return false;
        }

        $this->checkTokenInvalid();

        $office_event = $this->appEventToOfficeEvent($event);

        $options = $this->calendar->getParams() + $options;
        $res = array();
        try {
            $driver = $this->getDriver($options['token'], $options);
            $res = $driver->updateEvent($native_calendar_id, $event['native_event_id'], $office_event);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException($e);
        }

        if ($res && $res['changeKey'] !== ifset($event['params']['changeKey'])) {
            $event['params']['changeKey'] = $res['changeKey'];
            return $event;
        }

        return true;
    }

    public function deleteEvent($event, $options = array())
    {
        if (!$this->checkCalendar()) {
            return false;
        }
        $native_calendar_id = $this->calendar->getNativeCalendarId();
        if (!$native_calendar_id) {
            return false;
        }
        if (empty($event['native_event_id'])) {
            return false;
        }

        $this->checkTokenInvalid();

        $options = $this->calendar->getParams() + $options;
        try {
            $driver = $this->getDriver($options['token'], $options);
            $driver->deleteEvent($native_calendar_id, $event['native_event_id']);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException($e);
        }

        return true;
    }

    public function beforeRefreshToken()
    {
        if (!$this->calendar->getParam('token_invalid')) {
            $this->calendar->setParam('token_invalid', date('Y-m-d H:i:s'));
        }
    }

    public function afterRefreshToken($token)
    {
        $this->calendar->setParam('token', $token);
        $this->calendar->setParam('token_invalid', null);
    }

    public static function getTopBlockHtml()
    {
        $plugin_id = self::$plugin_id;
        $block_id = uniqid("t-{$plugin_id}-top-block");

        $html = '<p id=":block_id">' .
            _wp('To receive Application Id and secret <a href="https://apps.dev.microsoft.com/" target="_blank">register an application</a> on the Microsoft Application Registration portal.') .
            '<br>' .
			_wp('<a href="https://support.webasyst.com/16034/team-add-calendar-microsoft-office-365/" target="_blank">Step-by-step manual on connecting with a Microsoft Office Online</a>') .
            '</p>';

        $html .= '<script>$(function() {
            var block = $("#:block_id");
            var field_block = block.closest(".field");
            block.closest(".fields").before(block);
            field_block.remove();
        });</script>';

        return str_replace(':block_id', $block_id, $html);
    }

    public static function getBottomBlockHtml()
    {
        $plugin_id = self::$plugin_id;
        $callback_url = self::getCallbackUrlById($plugin_id);

        $block_id = uniqid("t-{$plugin_id}-bottom-block");

        $html = '<div id=":block_id" class="field">
                    <div class="name">' . _wp('Redirect URI') . '</div>
                    <div class="value">
                        <input type="text" class="t-callback-url-input" value=":callback_url" readonly><br>
                        <span class="hint">' . _wp('URL(https) where a user will return after Microsoft Graph authorization.') . '<br><strong>' .
                            _wp('Copy specified URL to the appropriate field in your Microsoft Graph application settings.') . '<br>'.
                            _wp('You need HTTPS to connect Microsoft account.').'</strong></span>
                    </div>
                </div>';

        $html .= '<script>$(function() {
            var block = $("#:block_id");
            var field_block = block.parent().closest(".field");
            field_block.replaceWith(block);
            var input = $(".t-callback-url-input", block);
            var val = input.val();
            var width = Math.round(val.length * 5.4);
            input.attr("style", "width: " + width + "px !important; max-width: 100% !important;");
            input.click(function() {
                $(this).select();
            });
        });</script>';

        return str_replace(
            array(':block_id', ':callback_url'),
            array($block_id, $callback_url),
            $html
        );
    }

    /**
     * @param string $token
     * @param array $options
     * @return teamOffice365Driver
     */
    protected function getDriver($token, $options = array())
    {
        if (empty($options['client_id'])) {
            $options['client_id'] = $this->client_id;
        }
        if (empty($options['client_secret'])) {
            $options['client_secret'] = $this->client_secret;
        }
        $options['redirect_uri'] = self::getCallbackUrlById(self::$plugin_id);
        $options['beforeRefreshTokenListener'] = array(
            array($this, 'beforeRefreshToken')
        );
        $options['afterRefreshTokenListener'] = array(
            array($this, 'afterRefreshToken')
        );
        return new teamOffice365Driver($token, $options);
    }


    protected function getOauth()
    {
        if (!$this->oauth) {
            $this->oauth = new teamOffice365Oauth(array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => self::getCallbackUrlById(self::$plugin_id)
            ));
        }
        return $this->oauth;
    }

    protected function isTokenInvalid()
    {
        return $this->calendar->getParam('token_invalid') || !$this->calendar->getParam('token');
    }

    protected function checkTokenInvalid()
    {
        if ($this->isTokenInvalid()) {
            $this->throwTokenInvalidException();
        }
    }

    protected function throwTokenInvalidException($msg_or_exception = 'Token is invalid')
    {
        if ($msg_or_exception instanceof teamCalendarExternalTokenInvalidException) {
            $e = $msg_or_exception;
        } else {
            $e = new teamCalendarExternalTokenInvalidException($msg_or_exception);
        }
        $e->setParams(array(
            'external_calendar_id' => $this->calendar->getId()
        ));
        throw $e;
    }

    protected function getTemplatePath($path)
    {
        return $this->path . '/templates/' . $path;
    }

    protected function renderTemplate($template_path, $assign = array())
    {
        $view = wa()->getView();
        $vars = $view->getVars();
        $view->clearAllAssign();
        $view->assign($assign);
        $res = $view->fetch($template_path);
        $view->clearAllAssign();
        $view->assign($vars);
        return $res;
    }

    protected function appEventToOfficeEvent($app_event)
    {
        $office_event = array();

        if (isset($app_event['summary'])) {
            $office_event['subject'] = (string) $app_event['summary'];
        }
        if (isset($app_event['is_allday'])) {
            $office_event['isAllDay'] = (bool) $app_event['is_allday'];
        }

        if (isset($app_event['description'])) {
            $office_event['body'] = array(
                'content' => (string) $app_event['description'],
                'contentType' => 'Text'
            );
        }

        $tz = waDateTime::getDefaultTimeZone();
        if (!$tz) {
            $tz = 'UTC';
        }

        $is_all_day = !empty($office_event['isAllDay']);

        if (isset($app_event['start'])) {
            $start_time = strtotime($app_event['start']);
            if ($is_all_day) {
                $office_event['start'] = array(
                    'DateTime' => date(self::DATE_YMD, $start_time),
                    'TimeZone' => $tz
                );
            } else {
                $office_event['start'] = array(
                    'DateTime' => date(self::DATE_ISO8601, $start_time),
                    'TimeZone' => $tz
                );
            }
        }
        if (isset($app_event['end'])) {
            $end_time = strtotime($app_event['end']);
            if (!empty($office_event['isAllDay'])) {
                $end_time = strtotime('+1 day', $end_time);
            }
            if ($is_all_day) {
                $office_event['end'] = array(
                    'DateTime' => date(self::DATE_YMD, $end_time),
                    'TimeZone' => $tz
                );
            } else {
                $office_event['end'] = array(
                    'DateTime' => date(self::DATE_ISO8601, $end_time),
                    'TimeZone' => $tz
                );
            }
        }

        return $office_event;
    }

    protected function listItemToEvent($item)
    {
        $start = null;
        $start_tz = null;
        if (isset($item['start']['dateTime'])) {
            $start = $item['start']['dateTime'];
            if (isset($item['start']['timeZone'])) {
                $start_tz = $item['start']['timeZone'];
            }
        }

        $end = null;
        $end_tz = null;
        if (isset($item['end']['dateTime'])) {
            $end = $item['end']['dateTime'];
            if (isset($item['end']['timeZone'])) {
                $end_tz = $item['end']['timeZone'];
            }
        }

        $location = ifset($item['location'], array());

        $body = ifset($item['body'], array());
        $body['contentType'] = strtolower(ifset($body['contentType'], 'text'));
        $body['content'] = ifset($body['content'], '');


        $event = array(
            'native_event_id' => $item['id'],
            'create_datetime' => $this->reformatDatetime($item['createdDateTime']),
            'update_datetime' => $this->reformatDatetime($item['lastModifiedDateTime']),
            'uid' => $item['iCalUId'],
            'summary' => $item['subject'],
            'description' => trim($body['contentType'] === 'text' ? $body['content'] : strip_tags($body['content'])),
            'location' => ifset($location['displayName'], ''),
            'start' => $start !== null ? ($item['isAllDay'] ? $this->reformatDate($start) : $this->reformatDatetime($start, $start_tz)) : $start,
            'end' => $end !== null ? ($item['isAllDay'] ? $this->reformatDate($end) : $this->reformatDatetime($end, $end_tz)) : $end,
            'is_allday' => $item['isAllDay'],
            'changeKey' => $item['changeKey']
        );

        if ($item['isAllDay']) {
            $event['end'] = $this->reformatDate(strtotime('-1 day', strtotime($event['end'])));
        }

        return $event;
    }

    protected function reformatDatetime($datetime, $tz = null)
    {
        if ($tz !== null) {
            $dtz = waDateTime::getDefaultTimeZone();
            if ($tz != $dtz) {
                $date_time = new DateTime($datetime, new DateTimeZone($tz));
                $date_time->setTimezone(new DateTimeZone($dtz));
                return $date_time->format(self::DATE_TIME);
            }
        }
        return date(self::DATE_TIME, strtotime($datetime));
    }

    protected function reformatDate($date)
    {
        $time = wa_is_int($date) ? $date : strtotime($date);
        return date(self::DATE_YMD, $time);
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

    private function debugLog($data, $tag)
    {
        if (waSystemConfig::isDebug()) {
            if (!is_scalar($data)) {
                $data = var_export($data, true);
            }
            $data = (string) $data;
            $data = join(PHP_EOL, array($tag, $data));
            waLog::log($data, 'team/office365/debug.log');
        }
    }
}
