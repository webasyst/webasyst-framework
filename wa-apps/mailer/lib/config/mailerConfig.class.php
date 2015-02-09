<?php

// Helper to chain constructor
if (!function_exists('with')) {
    /** @deprecated use wao() */
    function with($o) {
        return $o;
    }
}

// For backwards compatibility with old webasyst framework versions
if (!function_exists('wao')) {
    function wao($o) {
        if (!$o || !is_object($o)) {
            throw new waException('Argument is not an object.');
        }
        return $o;
    }
}
if (!function_exists('wa_is_int')) {
    function wa_is_int($i) {
        return int_ok($i);
    }
}

class mailerConfig extends waAppConfig
{
    const RETURN_PATH_CHECK_PERIOD = 2592000; // 30*24*3600;

    public function init()
    {
        parent::init();

        // init Swift Mailer
        require_once($this->getRootPath().'/wa-system/vendors/swift/swift_required.php');
    }

    protected function isIgnoreFile($f)
    {
        return parent::isIgnoreFile($f) || $f === 'swift';
    }

    public function checkUpdates()
    {
        try {
            $app_settings_model = new waAppSettingsModel();
            $time = $app_settings_model->get($this->application, 'update_time');
            if ($time == 'Array') {
                $app_settings_model->set($this->application, 'update_time', 1366642583);
            }
        } catch (waDbException $e) {
        }
        parent::checkUpdates();
    }

    public function getBounceTypes()
    {
        $result = $this->getOption('bounce_types');

        // Set up default values
        foreach ($result as $name => &$row) {
            if (!isset($row['fatal'])) {
                $row['fatal'] = true;
            }
            $row['name'] = $name;
            if (!isset($row['regex'])) {
                $row['regex'] = '~'.$name.'~i';
            }
        }

        return $result;
    }

    public function getAvailableSpeeds()
    {
        $speeds = wa('mailer')->getConfig()->getOption('sending_speeds');
        foreach($speeds as $num => &$s) {
            $s['num'] = $num;
            if (empty($s['name'])) {
                $s['name'] = _w('send not more than').' '.$num.' '._w('letters per hour');
            } else {
                $s['name'] = _w($s['name']);
            }
            if (empty($s['disabled'])) {
                $s['disabled'] = false;
            }
        }
        unset($s);

        return $speeds;
    }
}
