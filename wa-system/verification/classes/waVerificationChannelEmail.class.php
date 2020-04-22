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
        if (!$is_test_send) {
            $vca->updateById($asset_id, array('value' => $confirmation_hash));
        }

        $confirmation_url = $options['confirmation_url'];
        $confirmation_url = str_replace('{$confirmation_hash}', $confirmation_hash, $confirmation_url);

        // Prepare vars (assign array)
        $var_names = $this->getTemplateVars($template_name);
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

        if (!$result) {
            return false;
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

    protected function extractAssetIdFromHash($hash)
    {
        $asset_id = substr(substr($hash, 16), 0, -16);
        return $asset_id;
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
        $var_names = $this->getTemplateVars($template_name);
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
        $fail = array(
            'status' => false,
            'details' => array(
                'error' => self::VERIFY_ERROR_INVALID
            )
        );

        $confirmation_secret = is_scalar($confirmation_secret) ? (string)$confirmation_secret : '';
        if (strlen($confirmation_secret) <= 0) {
            return $fail;
        }

        $asset_id = $this->extractAssetIdFromHash($confirmation_secret);

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getOnce($asset_id);
        if (!$asset) {
            return $fail;
        }

        $recipient = null;
        if (isset($options['recipient'])) {
            $recipient = $this->typecastInputRecipient($options['recipient']);
        }

        $asset_contact_id = (int)$asset['contact_id'];

        if ($recipient !== null) {
            // check addresses
            if (!$this->isAddressEquals($asset['address'], $recipient['address'])) {
                return $fail;
            }

            // check contact ID
            if (isset($recipient['id']) && intval($recipient['id']) !== $asset_contact_id) {
                return $fail;
            }
        }

        // not recipient related checking
        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_HASH ||
            $asset['value'] != $confirmation_secret) {
            return $fail;
        }

        // successful validation result

        return array(
            'status' => true,
            'details' => array(
                'address' => $asset['address'],
                'contact_id' => $asset_contact_id
            ),
        );
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
        $validator = new waEmailValidator(array('required'=>true));
        return $validator->isValid($email);
    }

    protected function sendMessage($recipient, $subject, $body)
    {
        $status = $this->send($recipient, $subject, $body);
        $this->trackSendingStats($status);
        return $status;
    }

    private function send($recipient, $subject, $body)
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

            $asset_data = array(
                'channel_id' => $this->getId(),
                'address' => $recipient['email'],
                'name' => waVerificationChannelAssetsModel::NAME_ONETIME_PASSWORD,
                'value' => waContact::getPasswordHash($onetime_password)
            );
            if (isset($recipient['id'])) {
                $asset_data['contact_id'] = $recipient['id'];
            }

            $asset_id = $vca->setAsset($asset_data, '1 hour');

            if ($asset_id <= 0) {
                return false;
            }
        }

        // Prepare vars (assign array)
        $var_names = $this->getTemplateVars($template_name);
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
    public function sendConfirmationCodeMessage($recipient, $options = array())
    {
        $template_name = 'confirmation_code';

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

        if (isset($options['code'])) {
            $confirmation_code = $options['code'];
        } else {
            $confirmation_code = $this->generateCode();
        }

        $asset_id = 0;
        if (!$is_test_send) {
            $vca = new waVerificationChannelAssetsModel();
            $asset_id = $vca->set($this->getId(), $recipient['email'],
                waVerificationChannelAssetsModel::NAME_CONFIRMATION_CODE,
                waContact::getPasswordHash($confirmation_code), '1 hour');

            if ($asset_id <= 0) {
                return false;
            }
        }

        // Prepare vars (assign array)
        $var_names = $this->getTemplateVars($template_name);
        $vars = waUtils::extractValuesByKeys($options, $var_names, false, '');
        $vars['code'] = $confirmation_code;

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
            $key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_CONFIRMATION_CODE;
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
        $var_names = $this->getTemplateVars($template_name);
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

        if (!$result) {
            return false;
        }

        return $is_test_send ? $result : $asset_id;
    }

    /**
     * Validate secret that was in message
     * That secret grants rights to recovery (set new) password
     *
     * @see sendRecoveryPasswordMessage
     * @param string $secret
     * @param array $options
     *
     *   - 'recipient' If need to extra STRENGTHEN validation
     *
     *   - 'check_tries' Optional. Options for 'tries' logic. Format of options:
     *       - 'count' Number of tries to validate code.
     *              Default - is NULL, not take into account number of tries
     *              Otherwise - int
     *                  if number of calls of this method already greater than 'tries' than this method will be failed and return 'error'
     *      - 'clean' Delete asset after exceeding the number of tries
     *              Default is FALSE
     *
     *
     *     NOTICE: total number of tries is global for this for this asset (for this channel and recipient)
     *
     *
     * @return array Associative array
     *
     *   Format of this associative array:
     *
     *   - bool  'status'  - successful or not was validation
     *
     *   - array 'details' - detailed information about result of validation
     *      Format of details depends on 'status'
     *
     *        If 'status' is TRUE 'details' has keys:
     *
     *          - string 'address'     - address that was validated
     *          - int    'contact_id'  - id of contact bind to this address
     *          - int    'tries'       - total count of already made tries
     *          - int    'rest_tries'  - For convenience: count of rest tries. Formula is $options['check_tries']['count'] - $result['details']['tries']
     *
     *        Otherwise 'details' has keys:
     *
     *          - string   'error'      - string identificator of error - VERIFY_ERROR_* const
     *          - int|null 'tries'      - total count of already made tries. Can be NULL in case if code is already dead or not exist
     *          - int      'rest_tries' - For convenience: count of rest tries. Formula is $options['check_tries']['count'] - $result['details']['tries']
     *                                    But this value is NULL when 'tries' is NULL (in case if code is already dead or not exist)
     *
     * @throws waException
     */
    public function validateRecoveryPasswordSecret($secret, $options = array())
    {
        $hash = is_scalar($secret) ? (string)$secret : '';
        list($asset_id, $hash) = $this->parseHash($hash);
        $options['asset_id'] = $asset_id;
        $options['clean'] = false;
        return $this->validateSecret($hash, waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_HASH, $options);

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
        $var_names = $this->getTemplateVars($template_name);
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
     * Compare 2 secret for equal
     * @param $input_secret
     * @param $asset_secret
     * @param $asset_name
     * @return bool
     */
    protected function isSecretEquals($input_secret, $asset_secret, $asset_name)
    {
        if ($asset_name === waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_HASH || $asset_name === waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_HASH) {
            return $input_secret === $asset_secret;
        } else {
            return waContact::getPasswordHash($input_secret) === $asset_secret;
        }
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
    public function getTemplateVars($template_name, $with_description = false)
    {
        static $all_vars;
        if ($all_vars === null) {
            $all_vars = array(
                'confirm_signup' => array(
                    'site_name'        => _ws('Name of site that has sent a message'),
                    'site_url'         => _ws('Address of site that has sent a message'),
                    'confirmation_url' => _ws('Signup confirmation link URL')
                ),
                'onetime_password' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url'     => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'password'     => _ws('One-time password'),
                ),
                'password' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url'     => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'password'     => _ws('One-time password'),
                ),
                'recovery_password' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url'     => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'recovery_url' => _ws('Password recovery page URL')
                ),
                'successful_signup' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url'     => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'email'        => _ws('User email address'),
                    'password'     => _ws('Generated password')
                ),
                'confirmation_code' => array(
                    'site_name'    => _ws('Name of site that has sent a message'),
                    'site_url'     => _ws('Address of site that has sent a message'),
                    'login_url'    => _ws('Login page URL'),
                    'code'         => _ws('Confirmation code'),
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


    public function isWorking()
    {
        if (!$this->exists()) {
            return false;
        }

        $address = $this->getAddress();
        if (!$this->isValidEmail($address)) {
            return false;
        }

        // just heuristics

        $stats = $this->getSendingStats();
        if ($stats['last_failed'] >= 3) {
            return false;
        }

        return true;    // must be working
    }

    /**
     * Get diagnostic info about address of this channel
     * @return array<string, array<string, string>> $result
     *      Possible outcomes:
     *          Invalid format of channel email:
     *              array $result['invalid_format']
     *                  string $result['invalid_format']['text'] - text about error
     *          Invalid system sender:
     *              array $result['invalid_sender']
     *                  string $result['invalid_sender']['text'] - text about error
     *                  string $result['invalid_sender']['help_text'] - extra help text about error
     *          Bad (failed) sending statistics:
     *              array $result['failed_sending']
     *                  string $result['failed_sending']['text'] - text about error
     * @throws waException
     */
    public function getAddressDiagnostic()
    {
        $diagnostic = array();

        $sender = $this->getAddress();

        $sender = $this->isValidEmail($sender) ? $sender : null;
        if ($sender === null) {
            $diagnostic['invalid_format'] = array(
                'text' => _ws('Email address is not valid.')
            );
            return $diagnostic;
        }

        $system_mail_config = wa()->getConfig()->getMail();

        $system_default_sender = $this->getSystemDefaultSender();

        $domain = wa()->getConfig()->getDomain();
        $domain_has_email_sending_config = isset($system_mail_config[$domain]);

        $is_system_sender = $sender == $system_default_sender;
        $has_sending_config = $domain_has_email_sending_config || isset($system_mail_config[$sender]);

        if (!$is_system_sender && !$has_sending_config) {
            $text = _ws('Sender address %s is not configured.');
            $diagnostic['invalid_sender'] = array(
                'text' => sprintf($text, $sender),
                'help_text' => sprintf(
                    _ws('Configure a sender in “<a href="%s">%s</a>” section.'),
                    wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/email/',
                    _ws('Email settings')
                )
            );
        }

        if (!$diagnostic) {
            $send_stats = $this->getSendingStats();
            if (isset($send_stats['last_failed']) && $send_stats['last_failed'] >= 3) {
                $diagnostic['failed_sending'] = array(
                    'text' => _ws('Notification sending is not working.')
                );
            }
        }

        return $diagnostic;
    }

    protected function getSystemDefaultSender()
    {
        $sm = new waAppSettingsModel();
        $email = $sm->get('webasyst', 'sender', '');
        $v = new waEmailValidator(array('required'=>true));
        if ($v->isValid($email)) {
            return $email;
        }
        return null;
    }
}
