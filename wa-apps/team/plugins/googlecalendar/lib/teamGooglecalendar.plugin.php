<?php

/**
 * Class filesGoogledrivePlugin
 * @property-read $client_id
 * @property-read $client_secret
 */
class teamGooglecalendarPlugin extends teamCalendarExternalPlugin
{
    /**
     * @var teamGooglecalendarCalendar
     */
    private $oauth;

    const DATE_TIME_RFC3339 = 'Y-m-d\TH:i:sP';
    const DATE_YMD = 'Y-m-d';
    const DATE_TIME = 'Y-m-d H:i:s';

    public function authorizeBegin($id, $options = array())
    {
        try {
            $options['redirect_uri'] = self::getCallbackUrlById('googlecalendar');
            return $this->getOauth()->authorizeBegin($id, $options);
        } catch (teamGooglecalendarOauthException $e) {
            $e = new teamCalendarExternalAuthorizeFailedException($e->getMessage());
            $e->setParams(array(
                'id' => $id
            ));
            throw $e;
        }
    }

    public function authorizeEnd($options = array())
    {
        $options['redirect_uri'] = self::getCallbackUrlById('googlecalendar');

        try {
            $res = $this->getOauth()->authorizeEnd($options);
        } catch (teamGooglecalendarOauthException $e) {
            $msg = $e->getMessage();
            $params = $e->getParams();
            $e = new teamCalendarExternalAuthorizeFailedException($msg);
            $e->setParams(array(
                'id' => $params['id']
            ));
            throw $e;
        }

        $info = $this->getOauth()->getUserInfo($res['token']);
        foreach (array('email', 'id', 'name') as $field) {
            if (isset($info[$field])) {
                $res['user_' . $field] = $info[$field];
            }
        }

        $user_id = isset($res['user_id']) ? $res['user_id'] : null;

        $calendar = new teamCalendarExternal($res['id']);
        if ($calendar->getParam('user_id') && $calendar->getParam('user_id') != $user_id) {
            $e = new teamCalendarExternalAuthorizeFailedException(_wp('Another Google account'));
            $e->setParams(array(
                'id' => $calendar->getId()
            ));
            throw $e;
        }


        $res['token_invalid'] = null;

        return $res;
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

        $this->checkTokenInvalid();

        $driver = $this->getDriver($this->calendar->getParam('token'), $this->calendar->getParams());

        try {
            $res = $driver->getCalendars();
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException();
        }

        $this->debugLog($res, __METHOD__);
        $calendars = array();
        foreach (ifset($res['items'], array()) as $calendar) {
            $name = $calendar['summary'];
            $id = $calendar['id'];
            $calendars[] = array(
                'id' => $id,
                'name' => $name
            );
        }
        return $calendars;
    }

    public function getEvents($options = array())
    {
        if (!$this->checkCalendar()) {
            return array();
        }

        $this->checkTokenInvalid();

        $params = $this->calendar->getParams();
        $options = array_merge($params, $options);

        $driver = $this->getDriver($params['token'], $options);
        if (isset($options['min_time'])) {
            $options['timeMin'] = date(self::DATE_TIME_RFC3339, strtotime($options['min_time']));
        }

        try {
            $res = $driver->getEvents($this->calendar->getNativeCalendarId(), $options);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException();
        }

        $this->debugLog($res, __METHOD__);

        $events = array();
        foreach (ifset($res['items'], array()) as $item) {
            $event = $this->listItemToEvent($item);
            $events[] = $event;
        }

        $nextPageToken = ifset($res['nextPageToken']);
        $nextSyncToken = ifset($res['nextSyncToken']);

        return array(
            'info' => array(
                'pageToken' => $nextPageToken,
                'syncToken' => $nextSyncToken,
                'done' => empty($nextPageToken),
                'imported' => empty($nextPageToken)
            ),
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

    public function getChanges($options = array())
    {
        if (!$this->checkCalendar()) {
            return array();
        }

        $this->checkTokenInvalid();

        $options = array_merge($this->calendar->getParams(), $options);
        $driver = $this->getDriver($this->calendar->getParam('token'), $options);

        try {
            $res = $driver->getEvents($this->calendar->getNativeCalendarId(), $options);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException();
        }

        $this->debugLog($res, __METHOD__);

        $delete = array();
        $change = array();
        foreach (ifset($res['items'], array()) as $item) {
            if ($item['status'] === 'cancelled') {
                $delete[] = $item['id'];
            } else {
                $change[] = $this->listItemToEvent($item);;
            }
        }

        $nextPageToken = ifset($res['nextPageToken']);
        $nextSyncToken = ifset($res['nextSyncToken']);

        return array(
            'info' => array(
                'pageToken' => $nextPageToken,
                'syncToken' => $nextSyncToken,
                'done' => empty($nextPageToken)
            ),
            'change' => $change,
            'delete' => $delete,
        );
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

    protected function listItemToEvent($item)
    {
        $start = null;
        $start_tz = null;
        if (isset($item['start']['dateTime'])) {
            $start = $item['start']['dateTime'];
            if (isset($item['start']['timeZone'])) {
                $start_tz = $item['start']['timeZone'];
            }
        } else if (isset($item['start']['date'])) {
            $start = $item['start']['date'];
        }

        $end = null;
        $end_tz = null;
        if (isset($item['end']['dateTime'])) {
            $end = $item['end']['dateTime'];
            if (isset($item['end']['timeZone'])) {
                $end_tz = $item['end']['timeZone'];
            }
        } else if (isset($item['end']['date'])) {
            $end = $item['end']['date'];
        }

        $is_allday = isset($item['start']['date']) ? 1 : 0;

        $event = array(
            'native_event_id' => $item['id'],
            'create_datetime' => $this->reformatDatetime($item['created']),
            'update_datetime' => $this->reformatDatetime($item['updated']),
            'uid' => $item['iCalUID'],
            'summary' => $item['summary'],
            'description' => ifset($item['description'], ''),
            'location' => ifset($item['location'], ''),
            'sequence' => $item['sequence'],
            'start' => $start !== null ? ($is_allday ? $this->reformatDate($start) : $this->reformatDatetime($start, $start_tz)) : $start,
            'end' => $end !== null ? ($is_allday ? $this->reformatDate($end) : $this->reformatDatetime($end, $end_tz)) : $end,
            'is_allday' => $is_allday,
        );

        if ($is_allday) {
            $event['end'] = $this->reformatDate(strtotime('-1 day', strtotime($event['end'])));
        }


        return $event;
    }

    public static function getTopBlockHtml()
    {
        $block_id = uniqid("t-googlecalendar-top-block");

        $html = '<p id=":block_id">' .
            _wp('To receive Client ID and secret <a href="https://console.developers.google.com/" target="_blank">register an application</a> on the Google Developers Console.') .
            '<br>' .
			_wp('<a href="https://support.webasyst.com/16033/team-add-calendar-google/" target="_blank">Step-by-step manual on connecting with a Google Calendar</a>') .
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
        $callback_url = self::getCallbackUrlById('googlecalendar');

        $block_id = uniqid("t-googlecalendar-bottom-block");

        $html = '<div id=":block_id" class="field">
                    <div class="name">' . _wp('Authorized redirect URI') . '</div>
                    <div class="value">
                        <input type="text" class="t-callback-url-input" value=":callback_url" readonly><br>
                        <span class="hint">' . _wp('URL where a user will return after Google OAuth authorization.') . '<br><strong>' .
            _wp('Copy specified URL to the appropriate field in your Google application settings.') . '<br>
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

    public function beforeRefreshToken()
    {
        if (!$this->calendar->getParam('refreshing_start')) {
            $this->calendar->setParam('refreshing_start', date('Y-m-d H:i:s'));
        }
    }

    public function afterRefreshToken($token)
    {
        $this->calendar->deleteParam('refreshing_start');
        if (!$token) {
            $this->calendar->deleteParam('token');
            $this->calendar->setParam('token_invalid', date('Y-m-d H:i:s'));
        } else {
            $this->calendar->setParam('token', $token);
        }
    }

    /**
     * @param string $token
     * @param array $options
     * @return teamGooglecalendarDriver
     */
    protected function getDriver($token, $options = array())
    {
        if (empty($options['client_id'])) {
            $options['client_id'] = $this->client_id;
        }
        if (empty($options['client_secret'])) {
            $options['client_secret'] = $this->client_secret;
        }
        $options['beforeRefreshTokenListener'] = array(
            array($this, 'beforeRefreshToken')
        );
        $options['afterRefreshTokenListener'] = array(
            array($this, 'afterRefreshToken')
        );
        return new teamGooglecalendarDriver($token, $options);
    }

    private function getOauth()
    {
        if ($this->oauth === null) {
            $this->oauth = new teamGooglecalendarOauth($this->client_id, $this->client_secret);
        }
        return $this->oauth;
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
                    'user_email' => $this->calendar->getParam('user_email'),
                    'user_name' => $this->calendar->getParam('user_name'),
                ),
            ),
            'is_token_invalid' => $this->isTokenInvalid(),
            'action' => ifset($options['action'])
        ));
    }

    /**
     * @param $app_event
     * @return array
     */
    protected function appEventToGoogleEvent($app_event)
    {
        $google_event = array();
        foreach (array('summary', 'description', 'location', 'sequence') as $field) {
            if (isset($app_event[$field])) {
                $google_event[$field] = $app_event[$field];
            }
        }
        if (isset($app_event['is_allday'])) {
            $is_allday = $app_event['is_allday'];
            if (isset($app_event['start'])) {
                $start_time = strtotime($app_event['start']);
                if ($is_allday) {
                    $google_event['start'] = array(
                        'date' => date(self::DATE_YMD, $start_time),
                        'dateTime' => null
                    );
                } else {
                    $google_event['start'] = array(
                        'dateTime' => date(self::DATE_TIME_RFC3339, $start_time),
                        'date' => null
                    );
                }
            }
            if (isset($app_event['end'])) {
                $end_time = strtotime($app_event['end']);
                if ($is_allday) {
                    $end_time = strtotime('+1 day', $end_time);
                    $google_event['end'] = array(
                        'date' => date(self::DATE_YMD, $end_time),
                        'dateTime' => null
                    );
                } else {
                    $google_event['end'] = array(
                        'dateTime' => date(self::DATE_TIME_RFC3339, $end_time),
                        'date' => null
                    );
                }

            }
        }
        return $google_event;
    }

    /**
     * @param $event
     * @param array $options
     * @return bool
     * @throws teamCalendarExternalTokenInvalidException
     * @throws waException
     */
    public function updateEvent($event, $options = array())
    {
        if (!$this->checkCalendar()) {
            return false;
        }
        $native_event_id = $this->calendar->getNativeCalendarId();
        if (!$native_event_id) {
            return false;
        }
        if (empty($event['native_event_id'])) {
            return false;
        }

        $this->checkTokenInvalid();

        $google_event = $this->appEventToGoogleEvent($event);

        $options = $this->calendar->getParams() + $options;
        try {
            $driver = $this->getDriver($this->calendar->getParam('token'), $options);
            $driver->updateEvent($native_event_id, $event['native_event_id'], $google_event);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException();
        }

        return true;
    }

    /**
     * @param $event
     * @param array $options
     * @return bool
     * @throws teamCalendarExternalTokenInvalidException
     */
    public function addEvent($event, $options = array())
    {
        if (!$this->checkCalendar()) {
            return false;
        }
        $calendar_id = $this->calendar->getNativeCalendarId();
        if (!$calendar_id) {
            return false;
        }

        $this->checkTokenInvalid();

        $google_event = $this->appEventToGoogleEvent($event);

        $options = $this->calendar->getParams() + $options;
        $res = null;
        try {
            $driver = $this->getDriver($this->calendar->getParam('token'), $options);
            $res = $driver->addEvent($calendar_id, $google_event);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException();
        }

        if ($res) {
            $event = array_merge($event, $this->listItemToEvent($res));
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
            $driver = $this->getDriver($this->calendar->getParam('token'), $options);
            $driver->deleteEvent($native_calendar_id, $event['native_event_id']);
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $this->throwTokenInvalidException();
        }

        return true;
    }

    protected function isTokenInvalid()
    {
        $is_invalid = $this->calendar->getParam('token_invalid');
        if (!$is_invalid) {
            $refreshing_start = $this->calendar->getParam('refreshing_start');
            $_1min = 3600;
            if ($refreshing_start && time() - strtotime($refreshing_start) > $_1min) {
                $is_invalid = true;
            }
        }
        return $is_invalid;
    }

    protected function checkTokenInvalid()
    {
        if ($this->isTokenInvalid()) {
            $this->throwTokenInvalidException();
        }
    }

    protected function throwTokenInvalidException()
    {
        if (!$this->calendar->getParam('token_invalid')) {
            $this->calendar->setParam('token_invalid', date('Y-m-d H:i:s'));
            $this->calendar->deleteParam('refreshing_start');
            $this->calendar->deleteParam('token');
        }
        $e = new teamCalendarExternalTokenInvalidException();
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

    private function debugLog($data, $tag)
    {
        if (waSystemConfig::isDebug()) {
            if (!is_scalar($data)) {
                $data = var_export($data, true);
            }
            $data = (string) $data;
            $data = join(PHP_EOL, array($tag, $data));
            waLog::log($data, 'team/googlecalendar/debug.log');
        }
    }
}
