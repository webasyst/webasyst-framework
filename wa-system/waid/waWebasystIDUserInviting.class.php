<?php

class waWebasystIDUserInviting
{
    /**
     * @var waAppTokensModel
     */
    protected $atm;

    /**
     * @var string
     */
    protected $sender = '';

    /**
     * waWebasystIDUserInviting constructor.
     * @param array $options
     *      string $options['sender']
     */
    public function __construct(array $options = [])
    {
        $this->atm = new waAppTokensModel();
        if (isset($options['sender'])) {
            $this->sender = $options['sender'];
        }
    }

    /**
     * @param array $user
     *      - $user['id']
     *      - $user['email']
     *      - $user['name']
     *      - $user['locale']
     *      - $user['photo'] - photo ID (timestamp)
     * @return bool
     * @throws SmartyException
     * @throws waException
     */
    public function sendInvitation(array $user)
    {
        if (!$user['email']) {
            return false;
        }

        $template_path = wa()->getAppPath("templates/mail/WebasystIDInvite.html", 'webasyst');

        $user['name'] = waContactNameField::formatName($user);
        $user['userpic'] = $this->getDataResourceUrl(waContact::getPhotoUrl($user['id'], $user['photo'], null, null, 'person', true));

        $result = $this->generateOneTimeToken($user['id']);

        $site_domain = $this->getCurrentDomain();
        $site_url = wa()->getConfig()->getHostUrl();

        $sender_user = wa()->getUser();

        $sender_info = [
            'id' => $sender_user->getId(),
            'name' => waContactNameField::formatName($sender_user),
            'email' => $sender_user->get('email', 'default'),
            'userpic' => $this->getDataResourceUrl($sender_user->getPhoto())
        ];

        $subject = sprintf(_ws("[Action Required] %s invites you to upgrade to Webasyst ID on %s"), $sender_info['name'], mb_strtoupper($site_domain));

        $connect_link = waAppTokensModel::getLink($result['token']);

        $wa_content_url = wa()->getRootUrl(true) . 'wa-content/';

        $body = $this->renderTemplate($template_path, [
            'user' => $user,
            'connect_link' => $connect_link,
            'site_domain' => $site_domain,
            'site_url' => $site_url,
            'wa_content_url' => $wa_content_url,
            'sender_user' => $sender_info
        ]);

        return $this->sendEmail($subject, $body, $user['email']);
    }

    protected function getCurrentDomain()
    {
        $domain = wa()->getConfig()->getDomain();
        return waIdna::dec($domain);
    }

    protected function getDataResourceUrl($relative_url)
    {
        $cdn = wa()->getCdn($relative_url);
        if ($cdn->count() > 0) {
            return (string)$cdn;
        }
        $root_url = wa()->getRootUrl(true, false);
        return rtrim($root_url, '/') . '/' . ltrim($relative_url, '/');
    }

    protected function generateOneTimeToken($user_id)
    {
        $app_id = 'webasyst';
        $type = 'webasyst_id_invite';

        $token = $this->atm->getByField([
            'app_id'            => $app_id,
            'type'              => $type,
            'contact_id'        => $user_id,
        ]);

        $expire_datetime = date('Y-m-d H:i:s', time() + 3600 * 24 * 3);

        if ($token) {
            $update = [
                'expire_datetime'   => $expire_datetime
            ];
            $this->atm->updateById($token['token'], $update);
            return array_merge($token, $update);
        }

        return $this->atm->add([
            'app_id' => $app_id,
            'type' => $type,
            'contact_id' => $user_id,
            'create_contact_id' => wa()->getUser()->getId(),
            'expire_datetime' => $expire_datetime,
            'create_datetime' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function sendEmail($subject, $body, $to)
    {
        $sent = false;
        try {
            $m = new waMailMessage($subject, $body);
            $m->setTo($to, isset($recipient['name']) ? $recipient['name'] : null);
            $from = $this->sender;
            if ($from) {
                $m->setFrom($from);
            }
            $sent = (bool)$m->send();
        } catch (Exception $e) {
            $this->logException($e);
        }
        return $sent;
    }

    /**
     * Render template
     * @param string $template path to email template
     * @param array $assign key-value assign for template
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    protected function renderTemplate($template, array $assign = [])
    {
        $template = is_scalar($template) ? trim((string)$template) : '';
        if (strlen($template) <= 0) {
            return '';
        }

        $view = wa()->getView();
        $old_vars = $view->getVars();
        $view->clearAllAssign();

        $view->assign($assign);
        $result = $view->fetch($template);
        $view->clearAllAssign();
        $view->assign($old_vars);
        return $result;
    }

    protected function logException(Exception $e)
    {
        $file = 'webasyst_id_invite.log';
        $message = [
            $e->getMessage(),
            $e->getTraceAsString()
        ];
        $message = join(PHP_EOL, $message);
        waLog::log($message, $file);
    }
}
