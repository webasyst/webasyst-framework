<?php

class teamIcsPlugin extends teamCalendarExternalPlugin
{
    const NATIVE_CALENDAR_ID_IMPORTED_FROM_FILE = '#import_from_file';

    /**
     * @param $id
     * @param array $options
     * @return string
     */
    public function authorizeBegin($id, $options = array())
    {
        $template_path = $this->getTemplatePath('connect.html');
        return $this->renderTemplate($template_path, array(
            'id' => $id,
            'url' => self::getCallbackUrlById('ics')
        ));
    }

    /**
     * @param array $options
     * @return array
     */
    public function authorizeEnd($options = array())
    {
        $post = wa()->getRequest()->post();

        $calendar_external = new teamCalendarExternal($post['id']);
        $this->setCalendar($calendar_external);

        $res = $this->checkConnection($post);

        return $res;
    }

    public function checkConnection($params)
    {
        $id = (int) ifset($params['id']);

        $url = (string) ifset($params['url']);
        if (strlen($url) <= 0) {
            $this->raiseConnectionFailedError($id, _wp('URL is empty string'));
        }

        $parsed = parse_url($url);
        $scheme = ifset($parsed['scheme']);

        if ($scheme !== 'http' && $scheme !== 'https' && $scheme !== 'webcal' && $scheme !== 'webcals') {
            $this->raiseConnectionFailedError($id, _wp('URL scheme is unknown'));
        }
        $url = str_replace(array('webcal', 'webcals'), array('http', 'https'), $url);

        if (empty($parsed['host'])) {
            $this->raiseConnectionFailedError($id, _wp('URL host is empty'));
        }

        if (!$this->downloadFile($url)) {
            $this->raiseConnectionFailedError($id, _wp("Can't download by current URL"));
        }

        $filename = $this->getFilename($url);
        $parser = new teamIcsCalendarParser($filename);

        if (!$parser->hasBeginVCalendarStatement() && !$parser->hasEndVCalendarStatement()) {
            $this->raiseConnectionFailedError($id, _wp("No iCalendar found on specified URL"));
        }

        $name = $parser->getField('X-WR-CALNAME');
        if (strlen($name) <= 0) {
            $name = $parser->getField('PRODID');
        }
        $res['name'] = $name;

        return array(
            'id' => $id,
            'native_calendar_id' => $url,
            'name' => $name
        );
    }

    protected function getCalendarUrl()
    {
        $native_calendar_id = $this->calendar->getNativeCalendarId();
        if (!$native_calendar_id || $native_calendar_id == self::NATIVE_CALENDAR_ID_IMPORTED_FROM_FILE) {
            return '';
        }
        return $native_calendar_id;
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
        $url = $this->getCalendarUrl();
        if (!$url) {
            return array();
        }
        return array(
            array(
                'id' => $url,
                'name' => $this->calendar->getName()
            )
        );
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
        $url = $this->getCalendarUrl();
        if (!$url) {
            return array();
        }

        if (!$this->downloadFile($url)) {
            return array();
        }

        //TODO: make chunk case
        $filename = $this->getFilename($url);

        $parser = new teamIcsCalendarParser($filename);

        return array(
            'info' => array(
                'done' => true,
                'imported' => true
            ),
            'list' => $parser->getEvents(array('start_ts' => time()))
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
        return (bool) $this->calendar->getParam('imported');
    }

    /**
     * @param array $options
     * @return array|false
     */
    public function getChanges($options = array())
    {
        $res = $this->getEvents();

        $events = array();
        foreach (ifset($res['list'], array()) as $event) {
            $events[$event['uid']] = $event;
        }

        $m = new waModel();
        $sql = "SELECT wce.sequence, wce.update_datetime, wce.uid
                    FROM `wa_contact_events` wce
                    JOIN `team_event_external` tee ON wce.id = tee.event_id
                    WHERE tee.calendar_external_id = :id AND wce.start >= :start";
        $old_events_map = $m->query(
            $sql,
            array(
                'id' => $this->calendar->getId(),
                'start' => date('Y-m-d')
            )
        )->fetchAll('uid');

        $change = array();
        foreach ($events as $event) {
            $old_event_item = ifset($old_events_map[$event['uid']]);
            if (!$old_event_item) {
                $change[] = $event;
            } else if ($event['sequence'] > $old_event_item['sequence']) {
                $change[] = $event;
            } else if ($event['sequence'] == $old_event_item['sequence'] && strtotime($event['update_datetime']) > strtotime($old_event_item['update_datetime'])) {
                $change[] = $event;
            }
        }

        $delete = array();
        foreach ($old_events_map as $event) {
            if (!isset($events[$event['uid']])) {
                $delete[] = $event['uid'];
            }
        }

        return array(
            'change' => $change,
            'delete' => $delete,
            'info' => array()
        );
    }

    /**
     * @return mixed
     */
    public function isConnected()
    {
        return strlen((string) $this->calendar->getNativeCalendarId()) > 0;
    }

    /**
     * @param array $options
     * @return string
     */
    public function getAccountInfoHtml($options = array())
    {
        $native_calendar_id = $this->calendar->getNativeCalendarId();
        return $native_calendar_id === self::NATIVE_CALENDAR_ID_IMPORTED_FROM_FILE ? _w('Imported from file') : $native_calendar_id;
    }
    
    public function backendScheduleSettings($params)
    {
        /**
         * @var waContact $current_user
         */
        $current_user = $params['current_user'];
        $is_own = $current_user->getId() == wa()->getUser()->getId();
        if (!$is_own) {
            return array();
        }
        $template_path = $this->getTemplatePath('scheduleSettings.html');

        $tcm = new teamWaContactCalendarsModel();
        $inner_calendars = $tcm->getCalendars();

        $html = $this->renderTemplate($template_path, array(
            'inner_calendars' => $inner_calendars,
        ));
        return array('li' => $html);
    }

    protected function downloadFile($url)
    {
        $filename = $this->getFilename($url);

        $download = true;
        if (file_exists($filename)) {
            $mttime = filemtime($filename);
            $nowtime = time();
            $download = (($nowtime - $mttime) / 60) > 5;
        }

        if ($download) {
            $read = @fopen($url, 'r');
            if (!$read) {
                return false;
            }
            $write = @fopen($filename, 'a');
            $total_count = stream_copy_to_stream($read, $write);
            return $total_count > 0;
        }

        return true;
    }

    protected function getFilename($url)
    {
        $filename = md5(join('_', array($this->calendar->getId(), $url))) . '.ics';
        return wa()->getCachePath('plugins/ics/' . $filename, 'team');
    }

    protected function getTemplatePath($path)
    {
        return $this->path . '/templates/' . $path;
    }

    protected function renderTemplate($template_path, $assign = array())
    {
        waSystem::pushActivePlugin('ics', 'team');
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

    protected function raiseConnectionFailedError($id, $msg)
    {
        $e = new teamCalendarExternalAuthorizeFailedException($msg);
        $e->setParams(array(
            'id' => $id
        ));
        throw $e;
    }

    public function backendAssets()
    {
        wa()->getResponse()->addCss($this->getPluginStaticUrl(true).'css/ics.css');
    }

}
