<?php

class waVerificationChannelSMS extends waVerificationChannel
{
    protected $type = waVerificationChannelModel::TYPE_SMS;

    public function getType()
    {
        parent::getType();
        $this->typeMustConsistent(waVerificationChannelModel::TYPE_SMS);
        return $this->type;
    }

    /**
     * @param string|array|waContact|id $recipient recipient to send confirmation
     * @param array $options
     *   - bool 'use_session', use session for storage asset ID. Default false
     *
     * @return bool|int
     *   If 'use_session' == True - return bool
     *   Otherwise return int > 0 OR bool (if was failure)
     * @throws waDbException
     * @throws waException
     */
    public function sendSignUpConfirmationMessage($recipient, $options = array())
    {
        $template_name = 'confirm_signup';

        $template = $this->getTemplate($template_name);
        if (!$template) {
            return false;
        }
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $code = $this->generateCode();

        $vca = new waVerificationChannelAssetsModel();
        $asset_id = $vca->set($this->getId(), $recipient['phone'],
            waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_CODE,
            waContact::getPasswordHash($code), '5min');

        if ($asset_id <= 0) {
            return false;
        }

        $message = $this->renderTemplate($template, array(
            'code' => $code
        ));

        $result = $this->sendSMS($recipient['phone'], $message);

        // clean asset in failed sending
        if (!$result) {
            $vca->deleteById($asset_id);
            return false;
        }

        // use session to storage asset_id
        if (!empty($options['use_session'])) {
            $key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_CODE;
            wa()->getStorage()->set($key, $asset_id);
            return true;
        }

        return $asset_id;
    }

    /**
     * @param string|array|waContact|id $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options
     *   string 'password' password to send
     *
     * @return bool
     */
    public function sendSignUpSuccessNotification($recipient, $options = array())
    {
        $template = $this->getTemplate('successful_signup');
        if (!$template) {
            return false;
        }
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }
        $password = isset($options['password']) && is_scalar($options['password']) ? (string)$options['password'] : '';
        $message = $this->renderTemplate($template, array(
            'password' => $password
        ));
        return $this->sendSMS($recipient['phone'], $message);
    }

    /**
     * @param string $html_template - Send here the html template of any message to get a preview
     * @return string
     */
    public function previewMessage($html_template)
    {
        $code = $this->generateCode();
        $assign = array(
            'password' => $code,
            'code'  => $code,
        );
        return $this->renderTemplate($html_template,$assign);
    }

    protected function sendSMS($phone, $message)
    {
        $status = $this->send($phone, $message);
        $this->trackSendingStats($status);
        return $status;
    }

    private function send($phone, $message)
    {
        $phone = waContactPhoneField::cleanPhoneNumber($phone);
        $sms = new waSMS();
        return $sms->send($phone, $message, $this->getAddress());
    }

    /**
     * Validate signup code that was sent by SMS to recipient
     *
     * @param string $confirmation_secret signup code
     * @param array $options
     *
     *   - 'recipient' If need to extra STRENGTHEN validation
     *   - 'asset_id' Optional. Asset ID where is stored code. If skipped, get asset from session
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
    public function validateSignUpConfirmation($confirmation_secret, $options = array())
    {
        return $this->validateSecret($confirmation_secret, waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_CODE, $options);
    }

    protected function isAddressEquals($address1, $address2)
    {
        return waContactPhoneField::isPhoneEquals($address1, $address2);
    }

    protected function isSecretEquals($input_secret, $asset_secret, $asset_name)
    {
        return waContact::getPasswordHash($input_secret) === $asset_secret;
    }


    /**
     * @param array|string|waContact $recipient
     * @return array|null
     */
    protected function typecastInputRecipient($recipient)
    {
        if (is_scalar($recipient) && $this->isValidPhoneNumber($recipient)) {
            return array(
                'phone' => waContactPhoneField::cleanPhoneNumber((string)$recipient),
                'address' => waContactPhoneField::cleanPhoneNumber((string)$recipient)
            );
        }
        if (is_array($recipient)) {
            if (isset($recipient['phone']) || isset($recipient['address'])) {
                $phone = isset($recipient['phone']) ? $recipient['phone'] : $recipient['address'];
                $phone = waContactPhoneField::cleanPhoneNumber($phone);
                if ($this->isValidPhoneNumber($phone)) {
                    $result = array(
                        'phone' => $phone,
                        'address' => $phone
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
            $phone = $recipient->get('phone', 'default');
            if ($this->isValidPhoneNumber($phone)) {
                return array(
                    'id' => $recipient->getId(),
                    'phone' => waContactPhoneField::cleanPhoneNumber($phone),
                    'address' => waContactPhoneField::cleanPhoneNumber($phone),
                    'name' => $recipient->getName()
                );
            }
        }
        return null;
    }

    protected function isValidPhoneNumber($phone)
    {
        if (!is_scalar($phone)) {
            return false;
        }
        $validator = new waPhoneNumberValidator();
        return $validator->isValid($phone);
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
     *   - bool 'use_session', use session for storage asset ID. Default false
     *
     * @return bool|int
     *   If 'use_session' == True - return bool
     *   Otherwise return int > 0 OR bool (if was failure)
     * @throws waDbException
     * @throws waException
     */
    public function sendOnetimePasswordMessage($recipient, $options = array())
    {
        $template = $this->getTemplate('onetime_password');
        if (!$template) {
            return false;
        }
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $onetime_password = $this->generateOnetimePassword();

        $vca = new waVerificationChannelAssetsModel();

        $asset_data = array(
            'channel_id' => $this->getId(),
            'address' => $recipient['phone'],
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

        $message = $this->renderTemplate($template, array(
            'password' => $onetime_password
        ));

        $result = $this->sendSMS($recipient['phone'], $message);
        if (!$result) {
            $vca->deleteById($asset_id);
            return false;
        }

        // use session to storage asset_id
        if (!empty($options['use_session'])) {
            $key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_ONETIME_PASSWORD;
            wa()->getStorage()->set($key, $asset_id);
            return true;
        }

        return $asset_id;
    }

    /**
     *
     * Send message with confirmation code
     * By this code recipient can confirm that current channel (address) belongs to this recipient
     *
     * @param string|array|waContact|id $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' or 'phone' field - where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options
     *   - bool 'use_session', use session for storage asset ID. Default false
     *
     * @return bool|int
     *   If 'use_session' == True - return bool
     *   Otherwise return int > 0 OR bool (if was failure)
     * @throws waDbException
     * @throws waException
     */
    public function sendConfirmationCodeMessage($recipient, $options = array())
    {
        $template = $this->getTemplate('confirmation_code');
        if (!$template) {
            return false;
        }
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $confirmation_code = $this->generateCode();

        $vca = new waVerificationChannelAssetsModel();
        $asset_id = $vca->set($this->getId(), $recipient['phone'],
            waVerificationChannelAssetsModel::NAME_CONFIRMATION_CODE,
            waContact::getPasswordHash($confirmation_code), '1 hour');

        if ($asset_id <= 0) {
            return false;
        }

        $message = $this->renderTemplate($template, array(
            'code' => $confirmation_code
        ));

        $result = $this->sendSMS($recipient['phone'], $message);
        if (!$result) {
            $vca->deleteById($asset_id);
            return false;
        }

        // use session to storage asset_id
        if (!empty($options['use_session'])) {
            $key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_CONFIRMATION_CODE;
            wa()->getStorage()->set($key, $asset_id);
            return true;
        }

        return $asset_id;
    }

    protected function cleanAddress($address)
    {
        return waContactPhoneField::cleanPhoneNumber($address);
    }

    /**
     * Send message about recovery procedure with {$confirmation_code}
     * @param string|array|waContact|id $recipient recipient to send confirmation
     *
     * @param array $options
     *   - bool 'use_session', use session for storage asset ID. Default false
     *
     * @return bool|int
     *   If 'use_session' == True - return bool
     *   Otherwise return int > 0 OR bool (if was failure)
     * @throws waDbException
     * @throws waException
     */
    public function sendRecoveryPasswordMessage($recipient, $options = array())
    {
        $template = $this->getTemplate('recovery_password');
        if (!$template) {
            return false;
        }

        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $code = $this->generateCode();

        $vca = new waVerificationChannelAssetsModel();

        $asset_data = array(
            'channel_id' => $this->getId(),
            'address' => $recipient['phone'],
            'name' => waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_CODE,
            'value' => waContact::getPasswordHash($code)
        );
        if (isset($recipient['id'])) {
            $asset_data['contact_id'] = $recipient['id'];
        }

        $asset_id = $vca->setAsset($asset_data, '10min');
        if ($asset_id <= 0) {
            return false;
        }

        $message = $this->renderTemplate($template, array(
            'code' => $code
        ));

        $result = $this->sendSMS($recipient['phone'], $message);
        if (!$result) {
            $vca->deleteById($asset_id);
            return false;
        }

        // use session to storage asset_id
        if (!empty($options['use_session'])) {
            $key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_CODE;
            wa()->getStorage()->set($key, $asset_id);
            return true;
        }

        return $asset_id;
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
     *   - 'asset_id' Optional. Asset ID where is stored code. If skipped, get asset from session
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
        $options['clean'] = false;
        return $this->validateSecret($secret, waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_CODE, $options);
    }

    /**
     * @param $secret
     * @param array $options
     *
     *  - 'asset_id' int
     *       If isset than use it AS asset ID that says where secret placed in DB
     *       Otherwise try asset ID from session
     *              (see sendRecoveryPasswordMessage, validateRecoveryPasswordSecret )
     *
     *   - 'recipient' If need to extra STRENGTHEN validation
     *
     *
     * @throws waDbException
     * @throws waException
     *
     * @see sendRecoveryPasswordMessage
     * @see validateRecoveryPasswordSecret
     *
     * @return void
     *
     */
    public function invalidateRecoveryPasswordSecret($secret, $options = array())
    {
        $secret = is_scalar($secret) ? (string)$secret : '';
        if (strlen($secret) <= 0) {
            return;
        }

        $use_session = !isset($options['asset_id']);
        if ($use_session) {
            $session_key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_CODE;
            $asset_id = wa()->getStorage()->get($session_key);
            $asset_id = wa_is_int($asset_id) ? $asset_id : 0;
        } else {
            $asset_id = wa_is_int($options['asset_id']) ? $options['asset_id'] : 0;
        }

        if ($asset_id <= 0) {
            return;
        }

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getById($asset_id);
        if (!$asset) {
            return;
        }

        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_CODE ||
            $asset['value'] != waContact::getPasswordHash($secret)) {
            return;
        }

        $recipient = null;
        if (isset($options['recipient'])) {
            $recipient = $this->typecastInputRecipient($options['recipient']);
        }

        if ($recipient !== null && $asset['address'] !== $recipient['address']) {
            return false;
        }

        // clean asset (invalidate secret)
        $vca->deleteById($asset['id']);
        if ($use_session) {
            wa()->getStorage()->del($session_key);
        }
    }

    /**
     * @param array|string|waContact $recipient
     * @param string $password
     *
     * @param array $options
     *
     * @return bool|mixed
     */
    public function sendPassword($recipient, $password, $options = array())
    {
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $template = $this->getTemplate('password');
        if (!$template) {
            return false;
        }
        $message = $this->renderTemplate($template, array(
            'password' => $password
        ));
        return $this->sendSMS($recipient['phone'], $message);
    }

    public function getTemplatesList()
    {
        $templates_list = parent::getTemplatesList();
        unset($templates_list['recovery_password']);
        return $templates_list;
    }

    /**
     * Get vars name for each predefined template, optionally with description
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
                    'code' => _ws('Confirmation code'),
                ),
                'onetime_password' => array(
                    'password' => _ws('One-time password'),
                ),
                'password' => array(
                    'password' => _ws('New password'),
                ),
                'successful_signup' => array(
                    'password' => _ws('Generated password'),
                ),
                'confirmation_code' => array(
                    'code' => _ws('Confirmation code'),
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
        if (!$this->isValidPhoneNumber($address)) {
            return false;
        }

        // just heuristics

        $stats = $this->getSendingStats();
        if ($stats['last_failed'] >= 3) {
            return false;
        }

        return true;    // must be working
    }
}
