<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage user
 */
class waUser extends waContact
{

    protected $apps;

    public function init()
    {
        parent::init();
        if (!isset(self::$options['online_timeout'])) {
            self::$options['online_timeout'] = 300; // in secs
        }

        if (!isset(self::$options['activity_timeout'])) {
            self::$options['activity_timeout'] = 900; // in secs
        }
    }

    /**
     * get waUser by login
     *
     * @param string $login
     * @return waUser or null if no user with this login exists
     */
    public static function getByLogin($login)
    {
        $user_model = new waUserModel();
        if (! ( $row = $user_model->getByField('login', $login))) {
            return null;
        }
        return new waUser($row['id']);
    }

    /**
     * Returns login of the user
     *
     * @return string
     */
    public function getLogin()
    {
        return $this['login'];
    }

    public static function getStatusByInfo($info)
    {
        $timeout = self::$options['online_timeout']; // in sec
        if (isset($info['last_datetime']) && $info['last_datetime'] && $info['last_datetime'] != '0000-00-00 00:00:00') {
            if (time() - strtotime($info['last_datetime']) < $timeout) {
                $m = new waLoginLogModel();
                $datetime_out = $m->select('datetime_out')->
                        where('contact_id = i:0', array($info['id']))->
                        order('id DESC')->
                        limit(1)->fetchField();
                if (!$datetime_out) {
                    return 'online';
                } else {
                    return 'offline';
                }
            }
        }
        return 'offline';
    }

    public function getGroupIds()
    {
        $model = new waUserGroupsModel();
        $groups = $model->getGroupIds($this->id);
        $groups[] = -$this->id; // user
        $groups[] = 0; // all contacts
        return $groups;
    }


    public function offsetSet($offset, $value)
    {
        parent::offsetSet($offset, $value);
        if ($offset == 'password') {
                // set new auth token for current user
                if ($this->id == wa()->getUser()->getId()) {
                    wa()->getAuth()->updateAuth($this);
                }
        }
    }

    /**
     * Returns list of the users
     *
     * @param string $app_id - if specified returns only users whish has access to the application
     * @return array
     */
    public static function getUsers($app_id = null)
    {
        $contact_model = new waContactModel();
        if ($app_id) {
            $sql = "SELECT c.id, c.name
                    FROM ".$contact_model->getTableName()." c JOIN
                    wa_contact_rights r ON c.id = -r.group_id AND c.is_user = 1
                    WHERE (r.app_id = s:app_id OR (r.app_id = 'webasyst' AND r.name = 'backend')) AND r.value > 0
                    UNION
                    (SELECT c.id, c.name
                    FROM ".$contact_model->getTableName()." c JOIN
                    wa_user_groups g ON c.id = g.contact_id AND c.is_user = 1 JOIN
                    wa_contact_rights r ON g.group_id = r.group_id
                    WHERE (r.app_id = s:app_id OR (r.app_id = 'webasyst' AND r.name = 'backend')) AND r.value > 0
                    ) ORDER BY name";
        } else {
            $sql = "SELECT c.id, c.name FROM ".$contact_model->getTableName()." c
                    WHERE c.is_user = 1
                    ORDER BY c.name";
        }
        return $contact_model->query($sql, array('app_id' => $app_id))->fetchAll('id', true);
    }


    /**
     * Returns list of all user groups
     *
     * @example returns array(
     *     1 => 'Group 1',
     *     2 => 'Group 2',
     *     ...
     * )
     *
     * @return array - associative array with key group id and value group name
     */
    public static function getAllGroups()
    {
        $group_model = new waGroupModel();
        return $group_model->getNames();
    }


    public function getGroups($with_names = false)
    {
        $user_groups_model = new waUserGroupsModel();
        if ($with_names) {
            return $user_groups_model->getGroups($this->id);
        } else {
            return $user_groups_model->getGroupIds($this->id);
        }
    }

    public function getApps($sorted = true)
    {
        $apps = waSystem::getInstance()->getApps();

        $right_model = new waContactRightsModel();
        $rights = $right_model->getApps(-$this->id, 'backend', true, false);
        $is_admin = isset($rights['webasyst']) && $rights['webasyst'];

        $sorted_apps = array();
        if ($sorted) {
            $sort = explode(',', $this->getSettings('', 'apps'));
            foreach ($sort as $app_id) {
                if (!$is_admin && (!isset($rights[$app_id]) || !$rights[$app_id])) {
                    continue;
                }
                if (isset($apps[$app_id])) {
                    $sorted_apps[$app_id] = $apps[$app_id];
                    unset($apps[$app_id]);
                }
            }
        }
        foreach ($apps as $app_id => $app) {
            if (!$is_admin && (!isset($rights[$app_id]) || !$rights[$app_id])) {
                continue;
            }
            $sorted_apps[$app_id] = $app;
        }
        return $sorted_apps;
    }

    public static function revokeUser($id) {
        // wa_contact
        $user = new waContact($id);
        $user['is_user'] = 0;
        $user['login'] = null;
        $user['password'] = '';
        $user->save();

        // user groups
        $ugm = new waUserGroupsModel();
        $ugm->delete($id);

        // Access rigths
        $right_model = new waContactRightsModel();
        $right_model->deleteByField('group_id', -$id);

        // Custom application access rigths
        foreach(wa()->getApps() as $aid => $app) {
            if (isset($app['rights']) && $app['rights']) {
                $app_config = SystemConfig::getAppConfig($aid);
                $class_name = $app_config->getPrefix()."RightConfig";
                $file_path = $app_config->getAppPath('lib/config/'.$class_name.".class.php");
                $right_config = null;
                if (!file_exists($file_path)) {
                    continue;
                }
                waSystem::getInstance($aid, $app_config);
                include_once($file_path);
                /**
                 * @var waRightConfig $right_config
                 */
                $right_config = new $class_name();
                $right_config->clearRights($id);
            }
        }
    }
}

