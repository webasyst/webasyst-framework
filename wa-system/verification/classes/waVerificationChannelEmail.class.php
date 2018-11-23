<?php

class waVerificationChannelEmail extends waVerificationChannel
{
    const THEME_SEPARATOR = '{SEPARATOR}';

    protected $type = waVerificationChannelModel::TYPE_EMAIL;

    public function getType()
    {
        parent::getType();
        $this->typeMustConsistent(waVerificationChannelModel::TYPE_EMAIL);
        return $this->type;
    }

    /**
     * @param string $name
     * @see renderTemplate for template vars
     * @return array
     *  - string 'subject' Subject of email template
     *  - string 'text' Body of email template
     */
    public function getTemplate($name)
    {
        $template = parent::getTemplate($name);
        if (is_array($template)) {
            return $template;
        }
        if (mb_strpos($template, self::THEME_SEPARATOR) === false) {
            $template = self::THEME_SEPARATOR . $template;
        }
        list($subject, $text) = explode(self::THEME_SEPARATOR, $template);
        return array('subject' => $subject, 'text' => $text);
    }

    public function getBodyTemplate($name)
    {
        $template = $this->getTemplate($name);
        return $template['text'];
    }

    public function getSubjectTemplate($name)
    {
        $template = $this->getTemplate($name);
        return $template['subject'];
    }

    /**
     * Get default template(s)
     * @param null $name If null return all templates
     * @param null $loc If null use current user locale
     *
     * <template> is array of keys
     *   string 'subject'
     *   string 'text'
     *
     * @return mixed
     *   - <template> One template
     *   - array Array of format name => <template>
     */
    public function getDefaultTemplates($name = null, $loc = null)
    {
        $templates = parent::getDefaultTemplates($name, $loc);

        if ($name !== null) {
            if ($templates === null) {
                return null;
            }
            $templates = array($name => $templates);
        }

        foreach ($templates as &$template) {
            if (mb_strpos($template, self::THEME_SEPARATOR) === false) {
                $template = self::THEME_SEPARATOR . $template;
            }
            list($subject, $text) = explode(self::THEME_SEPARATOR, $template);
            $template = array('subject' => $subject, 'text' => $text);
        }
        unset($template);

        if ($name === null) {
            return $templates;
        } else {
            return $templates[$name];
        }
    }

    /**
     * @param string|array|waContact $recipient recipient to send confirmation
     *
     * @param array $options
     *
     *  - string 'confirmation_url' pattern for confirmation url, must have {$confirmation_hash} var in it
     *  - bool 'is_test_send' - If we need just test sending. Default value is False
     *
     *   Template vars also pass by $options each as separate option
     *   For list of template vars @see getTemplateVars
     *
     * @return bool|int
     *
     *   - On fail alwasy return FALSE
     *   - if is_test_send call return TRUE|FALSE
     *   - In other cases return int asset_id
     *
     */
    public function sendSignUpConfirmationMessage($recipient, $options = array())
    {
        $template_name = 'confirm_signup';

        $template = $this->getTemplate($template_name);
        if (!$template) {
            return false;
        }
        if (!isset($options['confirmation_url'])) {
            return false;
        }

        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $subject_template = $template['subject'];
        $text_template = $template['text'];

        $is_test_send = !empty($options['is_test_send']);

        $confirmation_hash = $this->generateHash();

        $vca = new waVerificationChannelAssetsModel();

        $asset_id = 0;
        if (!$is_test_send) {

            $asset_data = array(
                'channel_id' => $this->getId(),
                'address' => $recipient['email'],
                'name' => waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_HASH,
                'value' => $confirmation_hash
            );
            if (isset($recipient['id'])) {
                $asset_data['contact_id'] = $recipient['id'];
            }

            $asset_id = $vca->setAsset($asset_data, '24 hours');
            if ($asset_id <= 0) {
                return false;
            }
        }

        $confirmation_hash = $this->injectAssetIdIntoHash($asset_id, $confirmation_hash);

        $confirmation_url = $options['confirmation_url'];
        $confirmation_url = str_replace('{$confirmation_hash}', $confirmation_hash, $confirmation_url);

        // Prepare vars (assign array)
        $var_names = self::getTemplateVars($template_name);
        $vars = waUtils::extractValuesByKeys($options, $var_names, false, '');
        $vars['confirmation_url'] = $confirmation_url;

        // typecast all vars values to str
        waUtils::toStrArray($vars);

        // Render Body of Message
        $body = $this->renderTemplate($text_template, $vars);

        // Render Subject of Message
        $subject = $this->renderTemplate($subject_template, $vars);

        // Send message
        $result = $this->sendMessage($recipient, $subject, $body);

        // clean asset in failed sending
        if (!$result && $asset_id > 0) {
            $vca->deleteById($asset_id);
        }

        return $is_test_send ? $result : $asset_id;
    }

    protected function generateHash()
    {
        $salt = 'rfb2:zfbdbawrsddswr4$h5t3/.`w';
        $unique_id = uniqid(time().$salt.mt_rand().mt_rand().mt_rand(), true);
        $confirmation_hash = md5($unique_id);
        return $confirmation_hash;
    }

    protected function injectAssetIdIntoHash($asset_id, $hash)
    {
        return substr($hash, 0, 16) . $asset_id . substr($hash, -16);
    }

    protected function parseHash($hash)
    {
        $asset_id = substr(substr($hash, 16), 0, -16);
        $hash = substr($hash, 0, 16).substr($hash, -16);
        return array($asset_id, $hash);
    }

    /**
     * @param array|id|string|waContact $recipient
     *
     * @param array $options
     **
     *   Template vars also pass by $options each as separate option
     *   For list of template vars @see getTemplateVars
     *
     * @return bool
     */
    public function sendSignUpSuccessNotification($recipient, $options = array())
    {
        $template_name = 'successful_signup';

        $template = $this->getTemplate($template_name);
        if (!$template) {
            return false;
        }

        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $subject_template = $template['subject'];
        $text_template = $template['text'];

        // Prepare vars (assign array)
        $var_names = self::getTemplateVars($template_name);
        $vars = waUtils::extractValuesByKeys($options, $var_names, false, '');
        $vars['email'] = $recipient['email'];

        // typecast all vars values to str
        waUtils::toStrArray($vars);

        // Render body of message
        $body = $this->renderTemplate($text_template, $vars);

        // Render subject of message
        $subject = $this->renderTemplate($subject_template, $vars);

        return $this->sendMessage($recipient, $subject, $body);
    }

    /**
     * @param string $confirmation_secret
     * @param array $options
     *   - 'recipient' If need to extra STRENGTHEN validation
     *
     * @return array Associative array
     *
     *   Format of this associative array:
     *
     *   - bool  'status'  - successful or not was validation
     *
     *   - array 'details' - detailed information about result of validation
     *      Format of details depends on 'status'
     *        If 'status' is TRUE
     *          - string 'address'     - address that was validated
     *          - int    'contact_id'  - id of contact bind to this address
     *        Otherwise details is empty array
     */
    public function validateSignUpConfirmation($confirmation_secret, $options = array())
    {
        // Initialize result structure
        $result = array(
            'status' => false,
            'details' => array()
        );

        $confirmation_secret = is_scalar($confirmation_secret) ? (string)$confirmation_secret : '';
        if (strlen($confirmation_secret) <= 0) {
            return $result;
        }

        list($asset_id, $confirmation_hash) = $this->parseHash($confirmation_secret);

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getOnce($asset_id);
        if (!$asset) {
            return $result;
        }

        $recipient = null;
        if (isset($options['recipient'])) {
            $recipient = $this->typecastInputRecipient($options['recipient']);
        }

        $asset_contact_id = (int)$asset['contact_id'];

        if ($recipient !== null) {
            // check addresses
            if ($asset['address'] !== $recipient['address']) {
                return $result;
            }

            // check contact ID
            if (isset($recipient['id']) && intval($recipient['id']) !== $asset_contact_id) {
                return $result;
            }
        }

        // not recipient related checking
        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_HASH ||
            $asset['value'] != $confirmation_hash) {
            return $result;
        }

        // successful validation result

        $result['status'] = true;
        $result['details'] = array(
            'address' => $asset['address'],
            'contact_id' => $asset_contact_id
        );

        return $result;
    }

    /**
     * Typecast input $recipient to array with certain fields
     *
     * @param array|string|waContact $recipient
     * @return array|null
     *   If fail - return null
     *   Otherwise array with keys
     *     + string 'address'
     *     + string 'email' - same as 'address' - just alias
     *     + string 'status' (@see waContactEmailsModel and underlying table)
     *     + int 'id' - optional ID of recipient (e.g. waContact['id'])
     */
    protected function typecastInputRecipient($recipient)
    {
        if (is_scalar($recipient) && $this->isValidEmail($recipient)) {
            return array(
                'address' => (string)$recipient,
                'email' => (string)$recipient,
                'status' => 'unknown'
            );
        }
        if (is_array($recipient)) {
            if (isset($recipient['email']) || isset($recipient['address'])) {
                $email = isset($recipient['email']) ? $recipient['email'] : $recipient['address'];
                if ($this->isValidEmail($email)) {
                    $result = array(
                        'email' => $email,
                        'address' => $email,
                        'status' => 'unknown'
                    );
                    if (isset($recipient['name']) && is_scalar($recipient['name'])) {
                        $result['name'] = (string)$recipient['name'];
                    }
                    if (isset($recipient['id']) && wa_is_int($recipient['id'])) {
                        $result['id'] = $recipient['id'];
                    }
                    return $result;
                }
            }
        }
        if ($recipient instanceof waContact) {
            $email = $recipient->get('email', 'default');

            $status = 'unknown';
            $em = new waContactEmailsModel();
            $emails = $em->getEmails($recipient->getId());
            foreach ($emails as $email_info) {
                if ($email_info['value'] === $email) {
                    $status = $email_info['status'];
                }
            }

            if ($this->isValidEmail($email)) {
                return array(
                    'id' => $recipient->getId(),
                    'email' => $email,
                    'address' => $email,
                    'name' => $recipient->getName(),
                    'status' => $status
                );
            }
        }
        return null;
    }

    protected function isValidEmail($email)
    {
        if (!is_scalar($email)) {
            return false;
        }
        $validator = new waEmailValidator();
        return $validator->isValid($email);
    }

    protected function sendMessage($recipient, $subject, $body)
    {
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $status = ifset($recipient['status']);
        if ($status === 'unconfirmed') {
            return false;
        }

        try {
            $m = new waMailMessage($subject, $body);
            $m->setTo($recipient['email'], isset($recipient['name']) ? $recipient['name'] : null);
            $from = $this->getAddress();
            if ($from) {
                $m->setFrom($from);
            }
            return (bool)$m->send();
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    /**
     * @param string|array|waContact|id $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' or 'phone' field - where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options
     *   - bool 'use_session' - Use session for storage asset ID. Default false
     *   - bool 'is_test_send' - If we need just test sending. Default value is False
     *
     *   Template vars also pass by $options each as separate option
     *   For list of template vars @see getTemplateVars
     *
     * @return bool|int
     *   If 'use_session' == True OR 'is_test_send' == True - return bool
     *   Otherwise return int > 0 OR bool (if was failure)
     */
    public function sendOnetimePasswordMessage($recipient, $options = array())
    {
        $template_name = 'onetime_password';

        $template = $this->getTemplate($template_name);
        if (!$template) {
            return false;
        }
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $subject_template = $template['subject'];
        $text_template = $template['text'];

        $is_test_send = !empty($options['is_test_send']);

        $onetime_password = $this->generateOnetimePassword();

        $asset_id = 0;
        if (!$is_test_send) {
            $vca = new waVerificationChannelAssetsModel();
            $asset_id = $vca->set($this->getId(), $recipient['email'],
                waVerificationChannelAssetsModel::NAME_ONETIME_PASSWORD,
                waContact::getPasswordHash($onetime_password), '1 hour');

            if ($asset_id <= 0) {
                return false;
            }
        }

        // Prepare vars (assign array)
        $var_names = self::getTemplateVars($template_name);
        $vars = waUtils::extractValuesByKeys($options, $var_names, false, '');
        $vars['password'] = $onetime_password;

        // typecast all vars values to str
        waUtils::toStrArray($vars);

        // Render Body of Message
        $body = $this->renderTemplate($text_template, $vars);

        // Render Subject of Message
        $subject = $this->renderTemplate($subject_template, $vars);

        $result = $this->sendMessage($recipient, $subject, $body);
        if (!$result) {
            return false;
        }

        // use session to storage asset_id if non-test case
        if (!empty($options['use_session']) && !$is_test_send) {
            $key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_ONETIME_PASSWORD;
            wa()->getStorage()->set($key, $asset_id);
            return true;
        }

        return $is_test_send ? true : $asset_id;
    }

    /**
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options
     *  - string 'recovery_url' pattern for confirmation url, must have {$secret_hash} var in it
     *  - bool 'is_test_send' - If we need just test sending. Default value is False
     *
     *   Template vars also pass by $options each as separate option
     *   For list of template vars @see getTemplateVars
     *
     *   Other options depends on concrete implementation of method
     *
     * @return bool|int
     *
     *   - On fail alwasy return FALSE
     *   - if is_test_send call return TRUE|FALSE
     *   - In other cases return int asset_id
     */
    public function sendRecoveryPasswordMessage($recipient, $options = array())
    {
        $template_name = 'recovery_password';

        $template = $this->getTemplate($template_name);
        if (!$template) {
            return false;
        }
        if (!isset($options['recovery_url'])) {
            return false;
        }

        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $is_test_send = !empty($options['is_test_send']);

        $subject_template = $template['subject'];
        $text_template = $template['text'];

        $secret_hash = $this->generateHash();

        $vca = new waVerificationChannelAssetsModel();

        $asset_id = 0;
        if (!$is_test_send) {

            $asset_data = array(
                'channel_id' => $this->getId(),
                'address' => $recipient['email'],
                'name' => waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_HASH,
                'value' => $secret_hash
            );
            if (isset($recipient['id'])) {
                $asset_data['contact_id'] = $recipient['id'];
            }

            $asset_id = $vca->setAsset($asset_data, '24 hours');
            if ($asset_id <= 0) {
                return false;
            }
        }

        $secret_hash = $this->injectAssetIdIntoHash($asset_id, $secret_hash);

        $recovery_url = $options['recovery_url'];
        $recovery_url = str_replace('{$secret_hash}', $secret_hash, $recovery_url);

        // Prepare vars (assign array)
        $var_names = self::getTemplateVars($template_name);
        $vars = waUtils::extractValuesByKeys($options, $var_names, false, '');
        $vars['recovery_url'] = $recovery_url;

        // typecast all vars values to str
        waUtils::toStrArray($vars);

        // Render Body of message
        $body = $this->renderTemplate($text_template, $vars);

        // Render Subject of message
        $subject = $this->renderTemplate($subject_template, $vars);

        // Send message
        $result = $this->sendMessage($recipient, $subject, $body);

        // clean asset in failed sending
        if (!$result && $asset_id > 0) {
            $vca->deleteById($asset_id);
        }

        return $is_test_send ? $result : $asset_id;
    }

    /**
     * @param string $secret
     * @param array $options
     *   'recipient' If need to extra STRENGTHEN validation
     *
     * @return array Associative array
     *
     *   Format of this associative array:
     *
     *   - bool  'status'  - successful or not was validation
     *
     *   - array 'details' - detailed information about result of validation
     *      Format of details depends on 'status'
     *        If 'status' is TRUE
     *          - string 'address'     - address that was validated
     *          - int    'contact_id'  - id of contact bind to this address
     *        Otherwise details is empty array
     */
    public function validateRecoveryPasswordSecret($secret, $options = array())
    {
        // Initialize result structure
        $result = array(
            'status' => false,
            'details' => array()
        );

        $hash = is_scalar($secret) ? (string)$secret : '';
        if (strlen($hash) <= 0) {
            return $result;
        }

        list($asset_id, $hash) = $this->parseHash($hash);

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getById($asset_id);
        if (!$asset) {
            return $result;
        }

        $recipient = null;
        if (isset($options['recipient'])) {
            $recipient = $this->typecastInputRecipient($options['recipient']);
        }

        $asset_contact_id = (int)$asset['contact_id'];

        if ($recipient !== null) {
            // check addresses
            if ($asset['address'] !== $recipient['address']) {
                return $result;
            }

            // check contact ID
            if (isset($recipient['id']) && intval($recipient['id']) !== $asset_contact_id) {
                return $result;
            }
        }
        
        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_HASH ||
            $asset['value'] != $hash) {
            return $result;
        }

        // successful validation result

        $result['status'] = true;
        $result['details'] = array(
            'address' => $asset['address'],
            'contact_id' => $asset_contact_id
        );

        return $result;
    }

    public function invalidateRecoveryPasswordSecret($secret, $options = array())
    {
        $hash = is_scalar($secret) ? (string)$secret : '';
        if (strlen($hash) <= 0) {
            return;
        }

        list($asset_id, $hash) = $this->parseHash($hash);

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getById($asset_id);
        if (!$asset) {
            return;
        }

        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_HASH ||
            $asset['value'] != $hash) {
            return;
        }

        $vca->deleteById($asset['id']);
    }

    /**
     * Send message with new generated password
     *
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *
     * @param string $password password to send
     *
     * @param array $options
     *
     *   Template vars also pass by $options each as separate option
     *   For list of template vars @see getTemplateVars
     *
     *   Other options depends on concrete implementation of method
     *
     * @return mixed
     */
    public function sendPassword($recipient, $password, $options = array())
    {
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $template_name = 'password';

        $template = $this->getTemplate($template_name);
        if (!$template) {
            return false;
        }

        $subject_template = $template['subject'];
        $text_template = $template['text'];

        // Prepare vars (assign array)
        $var_names = self::getTemplateVars($template_name);
        $vars = waUtils::extractValuesByKeys($options, $var_names, false, '');
        $vars['password'] = $password;

        // typecast all vars values to str
        waUtils::toStrArray($vars);

        // Render body of message
        $body = $this->renderTemplate($text_template, $vars);

        // Render subject of message
        $subject = $this->renderTemplate($subject_template, $vars);

        return $this->sendMessage($recipient, $subject, $body);
    }

    /**
     * Get vars name for each predefined template, optionally with description
     * Need for extract names from $options in each send* methods
     * For example @see sendSignUpConfirmationMessage
     *
     * For list of available templates and description about they @see getTemplatesList
     *
     * @param string $template_name
     * @param bool $with_description
     * @return array
     *    If $with_description === True than return map <name_of_var> => <description_or_var>
     *    If $with_description === True than return array of <name_of_var>
     */
    public static function getTemplateVars($template_name, $with_description = false)
    {
        static $all_vars;
        if ($all_vars === null) {
            $all_vars = array(
                'confirm_signup' => array(
                    'site_name'        => _ws('Name of site that has sent a message'),
                    'site_url'     => _ws('Address of site that has sent a message'),
                    'confirmation_url' => _ws('Signup confirmation link URL')
                ),
                'onetime_password' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url' => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'password'     => _ws('One-time password'),
                ),
                'password' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url' => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'password'     => _ws('One-time password'),
                ),
                'recovery_password' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url' => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'recovery_url' => _ws('Password recovery page URL')
                ),
                'successful_signup' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url' => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'email'        => _ws('User email address'),
                    'password'     => _ws('Generated password')
                )
            );
        }
        if (!isset($all_vars[$template_name])) {
            return array();
        }
        $vars = $all_vars[$template_name];
        if ($with_description) {
            return $vars;
        }
        return array_keys($vars);
    }
}
