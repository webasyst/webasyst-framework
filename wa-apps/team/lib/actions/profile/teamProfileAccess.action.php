<?php
/**
 * User access tab in profile.
 */
class teamProfileAccessAction extends waViewAction
{
    protected $user;

    public function __construct($params = null)
    {
        parent::__construct($params);

        if (!empty($this->params['id'])) {
            $this->user = new waContact($this->params['id']);
            $this->user->getName();
        } else {
            $this->user = teamUser::getCurrentProfileContact();
        }
    }

    public function execute()
    {
        $user = $this->user;
        if (!$this->hasAccess($user)) {
            throw new waException('Contact not found', 404);
        }

        $auth = wa()->getAuthConfig();
        $personal_portal_available = !empty($auth['app']);

        $user_groups_model = new waUserGroupsModel();
        $groups = $user_groups_model->getGroups($user->getId());

        $contact_rights_model = new waContactRightsModel();
        $ownAccess = $contact_rights_model->getApps(-$user->getId(), 'backend', false, false) + array('webasyst' => 0);
        $groupAccess = $contact_rights_model->getApps(array_keys($groups), 'backend', false, false) + array('webasyst' => 0);
        $apps = teamHelper::appsWithAccessRights($ownAccess, $groupAccess);

        $noAccess = true;
        $gNoAccess = true;
        foreach ($apps as $app) {
            $noAccess = $noAccess && !$app['gaccess'] && !$app['access'];
            $gNoAccess = $gNoAccess && !$app['gaccess'];
        }

        if (wa()->getUser()->getId() == $user->getId()) {
            $url_change_password = wa_backend_url().'?module=profile&action=password';
        } else {
            $url_change_password = wa('team')->getUrl().'?module=accessSave&action=password&id='.$user['id'];
        }

        if (wa()->getUser()->getId() == $user->getId()) {
            $url_change_api_token = wa_backend_url().'?module=profile&action=api';
        } else {
            $url_change_api_token = wa('team')->getUrl().'?module=accessSave&action=api&id='.$user['id'];
        }

        $app_tokens_model = new waAppTokensModel();
        $app_tokens_model->purge();
        $invite_tokens = $app_tokens_model->getByField(array(
            'app_id'     => 'team',
            'type'       => 'user_invite',
            'contact_id' => $user['id'],
        ), true);

        $group_model = new waGroupModel();
        $this->view->assign(array(
            'contact'                   => $user,
            'access_disable_msg'        => $this->getAccessDisableMsg($user),
            'personal_portal_available' => $personal_portal_available,

            'own_profile'   => wa()->getUser()->getId() == $user->getId(),
            'is_superadmin' => wa()->getUser()->isAdmin(),

            'gFullAccess' => $groupAccess['webasyst'],
            'fullAccess'  => $ownAccess['webasyst'],
            'gNoAccess'   => (int)$gNoAccess,
            'noAccess'    => (int)$noAccess,

            'url_change_password' => $url_change_password,

            'apps'             => $apps,
            'groups'           => $groups,
            'all_groups'       => $group_model->getNames(),
            'access_types'     => teamHelper::getAccessTypes(),
            'invite_tokens'    => $invite_tokens,

            'api_tokens'           => $this->getApiTokens(),
            'url_change_api_token' => $url_change_api_token,

            'email_change_log' => $this->getEmailChangeLog()
        ));
    }

    protected static function hasAccess($user)
    {
        if ($user->getId() == wa()->getUser()->getId()) {
            return true;
        }
        return wa()->getUser()->isAdmin();
    }
    /**
     * @param waContact $user
     * @return string
     */
    public static function getAccessDisableMsg($user)
    {
        if ($user['is_user'] != -1) {
            return '';
        }

        $log_model = new waLogModel();
        $log_item = $log_model->select('*')->where(
            "subject_contact_id = i:id AND action = 'access_disable'",
            array('id' => $user['id'])
        )->order('id DESC')->limit(1)->fetch();
        if (!$log_item) {
            return '';
        }

        $contact = new waContact($log_item['contact_id']);
        try {
            $name = $contact->getName();
        } catch (Exception $e) {
            $name = _w('deleted contact_id=').$log_item['contact_id'];
        }
        return sprintf_wp(
            'Access disabled by %1$s, %2$s',
            sprintf(
                '<a href="%s">%s</a>',
                teamUser::link($contact),
                htmlspecialchars($name)
            ),
            wa_date("humandatetime", $log_item['datetime'])
        );
    }

    protected function getEmailChangeLog()
    {
        $log = $this->getLogModel()->getLogs(array(
            'action' => 'my_profile_edit',
            'contact_id' => $this->user->getId()
        ));
        $email_change_log = array();
        foreach ($log as $item) {
            $params = json_decode($item['params'], true);
            if (!isset($params['email']) || !is_array($params['email'])) {
                continue;
            }
            $emails = array();
            foreach ($params['email'] as $email) {
                if (is_array($email) && isset($email['value'])) {
                    $email = $email['value'];
                }
                if (is_string($email) && strlen($email) > 0) {
                    $emails[] = $email;
                }
            }
            if (!$emails) {
                continue;
            }
            $email_change_log[] = array(
                'id' => $item['id'],
                'datetime' => $item['datetime'],
                'emails' => $emails
            );
        }
        return $email_change_log;
    }

    protected function getApiTokens()
    {
        $apps = wa()->getApps();
        $tokens_model = new waApiTokensModel();
        $api_tokens = $tokens_model->getByField(array('contact_id' => $this->user->getId()),true);
        foreach ($api_tokens as &$token) {
            // Get scope apps images and names
            $token['installed_apps'] = $token['not_installed_apps'] =  array();
            $token_apps = explode(',', $token['scope']);
            foreach ($token_apps as $app) {
                if (array_key_exists($app, $apps)) {
                    $token['installed_apps'][] = array(
                        'img' => ifempty($apps[$app]['img']),
                        'name'  => ifempty($apps[$app]['name'], $app),
                    );
                } else {
                    $token['not_installed_apps'][] = $app;
                }
            }
        }
        unset($token);
        return $api_tokens;
    }

    /**
     * @return waLogModel
     */
    protected function getLogModel()
    {
        static $model;
        if ($model) {
            return $model;
        }
        if (!class_exists('waLogModel')) {
            wa('webasyst');
        }
        return $model = new waLogModel();
    }
}
