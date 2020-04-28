<?php

class teamHelper
{
    protected static $users = array();
    protected static $wa_groups = array();

    public static function getUsers($add_all = true)
    {
        if (!self::$users) {
            self::$users = teamUser::getList('users/all', array(
                'add_item_all' => true,
                'fields' => 'minimal',
                'order' => 'name',
            ));
        }
        if ($add_all) {
            return self::$users;
        } else {
            $result = self::$users;
            unset($result['all']);
            return $result;
        }
    }

    public static function getUrl($key = null, $value = null)
    {
        $url = parse_url(waRequest::server('REQUEST_URI'));
        $url = $url['path'];
        $get = waRequest::get();
        if ($key) {
            if ($value) {
                $get[$key] = $value;
            } else {
                unset($get[$key]);
            }
        }
        if (isset($get['_'])) {
            unset($get['_']);
        }
        if ($get) {
            $url .= '?'.http_build_query($get);
        }
        return $url;
    }

    public static function isAjax()
    {
        $is_ajax = waRequest::request('is_ajax', null, waRequest::TYPE_INT);
        if ($is_ajax !== null) {
            return !!$is_ajax;
        }
        return waRequest::isXMLHttpRequest();
    }

    /** Convert datetime to localized format without changing timezone. */
    public static function date($time, $format = 'date')
    {
        return waDateTime::format($format, $time, waDateTime::getDefaultTimeZone());
    }

    public static function getAbsoluteUrl()
    {
        $config = wa()->getConfig();
        $url = $config->getRootUrl(true) . $config->getBackendUrl() . '/team/';
        return $url;
    }

    /**
     * Convert first symbol to upper case. Exists for symmetry with lcfirst
     * @see filesApp::lcfirst
     * @param string $str
     * @return string
     */
    public static function ucfirst($str)
    {
        if (strlen($str) <= 0) {
            return $str;
        }
        return strtoupper(substr($str, 0, 1)) . substr($str, 1);
    }

    /**
     * Convert first symbol to lower case. Standard lcfirst isn't supported php below 5.3 version
     * @param string $str
     * @return string
     */
    public static function lcfirst($str)
    {
        if (strlen($str) <= 0) {
            return $str;
        }
        return strtolower(substr($str, 0, 1)) . substr($str, 1);
    }

    /**
     * "...whether I am a trembling creature or whether I have the right..."
     *
     * With an argument: asks for particular access right.
     * No arguments: asks for full access to application.
     */
    public static function hasRights($right = null)
    {
        if ($right) {
            return wa()->getUser()->getRights('team', $right);
        } else {
            return wa()->getUser()->isAdmin('team');
        }
    }

    public static function getWaGroups()
    {
        if (!self::$wa_groups) {
            $gm = new waGroupModel();
            self::$wa_groups = $gm->select('*')->order('sort')->fetchAll('id');
        }
        return self::$wa_groups;
    }

    public static function getVisibleGroups($user = null)
    {
        $visible_groups = array();
        $user = $user ? $user : wa()->getUser();
        foreach (self::getWaGroups() as $id => $g) {
            if ($g['type'] == 'group' && $user->getRights('team', 'manage_users_in_group.'.$id) >= 0) {
                $visible_groups[$id] = $g;
            }
        }
        return $visible_groups;
    }

    public static function getVisibleLocations($user = null)
    {
        $visible_locations = array();
        $user = $user ? $user : wa()->getUser();
        foreach (self::getWaGroups() as $id => $g) {
            if ($g['type'] == 'location' && $user->getRights('team', 'manage_users_in_group.'.$id) >= 0) {
                $visible_locations[$id] = $g;
            }
        }
        return $visible_locations;
    }

    public static function groupRights($groups, $user = null)
    {
        $right = 'manage_users_in_group';
        $res = array();
        $user = $user ? $user : wa()->getUser();
        foreach ($groups as $id => $g) {
            if ($user->getRights('team', $right.'.'.$id) >= 0) {
                $res[$id] = $g;
            }
        }
        return $res;
    }

    /** Throw waRightsException if current contact is not allowed
     * to modify events in given calendar for given contact. */
    public static function checkCalendarRights($calendar_id, $contact_id)
    {
        if (!$calendar_id || wa()->getUser()->isAdmin(wa()->getApp())) {
            return;
        }
        $tcm = new teamWaContactCalendarsModel();
        $calendar = $tcm->getCalendar($calendar_id, $contact_id);
        if (empty($calendar['can_edit'])) {
            throw new waRightsException();
        }
        if ($contact_id != wa()->getUser()->getId() && !teamHelper::hasRights()) {
            $contact = new waContact($contact_id);
            /*
            $has_rights = false;
            if ($all_groups = $contact->getRights('team', 'manage_users_in_group.%')) {
                $all_groups = join("','", array_keys($all_groups));
                $gm = new waGroupModel();
                $has_rights = $gm->select('*')->where(
                    "g.contact_id=$contact_id AND group_id IN('$all_groups')"
                )->fetchAll();
            }
            */
            if (!self::getVisibleGroups($contact) && !self::getVisibleLocations($contact)) {
                throw new waRightsException();
            }
        }
    }

    public static function getAccessTypes()
    {
        $result = array();
        foreach (array(
            "no" => _w("No access"),
            "limited" => _w("Limited access"),
            "full" => _w("Full access"),
        ) as $id => $name) {
            $result[$id] = array(
                'id' => $id,
                'name' => $name,
                'is_active' => false,
            );
        }
        return $result;
    }

    public static function appsWithAccessRights($ownAccess, $groupAccess = array())
    {
        $apps = wa()->getApps();
        uasort($apps, wa_lambda('$app1, $app2', 'return strcoll($app1["name"], $app2["name"]);'));
        foreach ($apps as $app_id => &$app) {
            $app['id'] = $app_id;
            $app['customizable'] = isset($app['rights']) ? (boolean) $app['rights'] : false;
            $app['access'] = !empty($ownAccess['webasyst']) ? 2 : 0;
            if (!$app['access'] && isset($ownAccess[$app_id])) {
                $app['access'] = $ownAccess[$app_id];
            }
            $app['gaccess'] = !empty($groupAccess['webasyst']) ? 2 : 0;
            if (!$app['gaccess'] && isset($groupAccess[$app_id])) {
                $app['gaccess'] = $groupAccess[$app_id];
            }
        }
        unset($app);
        return $apps;
    }

    public static function sendEmailSimpleTemplate($address, $template_id, $vars, $from = null)
    {
        if (!$address || !$template_id || waConfig::get('is_template')) {
            return false;
        }
        $format = 'text/html';
        if (empty($vars['{LOCALE}'])) {
            $vars['{LOCALE}'] = 'en_US';
        }
        $locale = $vars['{LOCALE}'];

        $template_file = wa()->getAppPath().'/templates/messages/'.$template_id.'.'.$locale.'.html';

        // Look for template in appropriate locale
        if (!is_readable($template_file)) {
            throw new waException('Mail template file is not readable: '.$template_file);
        }

        // Load template and replace $vars
        $message = @file_get_contents($template_file);
        $message = explode('{SEPARATOR}', $message);
        if (empty($message[1])) {
            throw new waException('Bad template file format: '.$template_file);
        }
        $subject = trim($message[0]);
        $content = trim($message[1]);

        $subject = str_replace(array_keys($vars), array_values($vars), $subject);
        $content = str_replace(array_keys($vars), array_values($vars), $content);

        // Send message
        $res = true;
        try {
            $mailer = new waMailMessage($subject, $content, $format);
            $mailer->setTo($address);
            if ($from) {
                $mailer->setFrom($from);
            }
            if (!$mailer->send()) {
                $res = false;
            }
        } catch (Exception $e) {
            $res = false;
        }
        return $res;
    }

    public static function isBanned($contact)
    {
        return $contact['is_user'] == -1 && $contact['login'];
    }
}
