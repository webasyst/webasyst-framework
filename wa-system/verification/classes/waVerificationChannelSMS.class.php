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
        if (!$result && $asset_id > 0) {
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
        $phone = waContactPhoneField::cleanPhoneNumber($phone);
        $sms = new waSMS();
        return $sms->send($phone, $message, $this->getAddress());
    }

    /**
     * @param string $confirmation_secret
     * @param array $options
     *
     *  - 'asset_id' int
     *       If isset than use it AS asset ID that says where secret placed in DB
     *       Otherwise try asset ID from session ( @see sendSignUpConfirmationMessage )
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

        $use_session = !isset($options['asset_id']);

        $session_key = '';
        if ($use_session) {
            $session_key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_CODE;
            $asset_id = wa()->getStorage()->get($session_key);
            $asset_id = wa_is_int($asset_id) ? $asset_id : 0;
        } else {
            $asset_id = wa_is_int($options['asset_id']) ? $options['asset_id'] : 0;
        }

        if ($asset_id <= 0) {
            return $result;
        }
        $confirmation_code = $confirmation_secret;

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getById($asset_id);
        if (!$asset) {
            return $result;
        }

        $recipient = null;
        if (isset($options['recipient'])) {
            $recipient = $this->typecastInputRecipient($options['recipient']);
        }

        if ($recipient !== null && !waContactPhoneField::isPhoneEquals($asset['address'], $recipient['address'])) {
            return $result;
        }

        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_CODE ||
            $asset['value'] != waContact::getPasswordHash($confirmation_code)) {
            return $result;
        }

        // clean asset
        $vca->deleteById($asset['id']);
        if ($use_session) {
            wa()->getStorage()->del($session_key);
        }

        // successful validation result

        $result['status'] = true;
        $result['details'] = array(
            'address' => waContactPhoneField::cleanPhoneNumber($asset['address']),
            'contact_id' => $asset['contact_id']
        );

        return $result;
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
                    return $result;
                }
            }
        }
        if ($recipient instanceof waContact) {
            $phone = $recipient->get('phone', 'default');
            if ($this->isValidPhoneNumber($phone)) {
                return array(
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

    protected function generateCode($len = 4)
    {
        $code = array();
        for ($i = 0; $i < $len; $i++) {
            $code[] = rand(0, 9);
        }
        return join('', $code);
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
        $asset_id = $vca->set($this->getId(), $recipient['phone'],
            waVerificationChannelAssetsModel::NAME_ONETIME_PASSWORD,
            waContact::getPasswordHash($onetime_password), '1 hour');

        if ($asset_id <= 0) {
            return false;
        }

        $message = $this->renderTemplate($template, array(
            'password' => $onetime_password
        ));

        $result = $this->sendSMS($recipient['phone'], $message);
        if (!$result) {
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

    public function validateOnetimePassword($password, $options = array())
    {
        $result = parent::validateOnetimePassword($password, $options);
        return is_bool($result) ? $result : waContactPhoneField::cleanPhoneNumber($result);
    }

    /**
     * Send message about recovery procedure with {$conirmation_code}
     * @param string|array|waContact|id $recipient recipient to send confirmation
     *
     * @param array $options
     *   - bool 'use_session', use session for storage asset ID. Default false
     *
     * @return bool|int
     *   If 'use_session' == True - return bool
     *   Otherwise return int > 0 OR bool (if was failure)
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
        $asset_id = $vca->set($this->getId(), $recipient['phone'],
            waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_CODE,
            waContact::getPasswordHash($code), '10min');
        if ($asset_id <= 0) {
            return false;
        }

        $message = $this->renderTemplate($template, array(
            'code' => $code
        ));

        $result = $this->sendSMS($recipient['phone'], $message);
        if (!$result) {
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
     * @param string $secret
     * @param array $options
     *
     *  - 'asset_id' int
     *       If isset than use it AS asset ID that says where secret placed in DB
     *       Otherwise try asset ID from session ( @see sendSignUpConfirmationMessage )
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

        $secret = is_scalar($secret) ? (string)$secret : '';
        if (strlen($secret) <= 0) {
            return $result;
        }

        $session_key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_CODE;

        if (array_key_exists('asset_id', $options)) {
            $asset_id = wa_is_int($options['asset_id']) ? $options['asset_id'] : 0;
        } else {
            $asset_id = wa()->getStorage()->get($session_key);
            $asset_id = wa_is_int($asset_id) ? $asset_id : 0;
        }

        if ($asset_id <= 0) {
            return $result;
        }

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getById($asset_id);
        if (!$asset) {
            return $result;
        }

        $recipient = null;
        if (isset($options['recipient'])) {
            $recipient = $this->typecastInputRecipient($options['recipient']);
        }

        if ($recipient !== null && !waContactPhoneField::isPhoneEquals($asset['address'], $recipient['address'])) {
            return $result;
        }

        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_PASSWORD_RECOVERY_CODE ||
            $asset['value'] != waContact::getPasswordHash($secret)) {
            return $result;
        }

        $result['status'] = true;
        $result['details'] = array(
            'address' => waContactPhoneField::cleanPhoneNumber($asset['address']),
            'contact_id' => $asset['contact_id']
        );

        return $result;
    }

    /**
     * @param $secret
     * @param array $options
     *
     *  - 'asset_id' int
     *       If isset than use it AS asset ID that says where secret placed in DB
     *       Otherwise try asset ID from session
     *              ( @see sendRecoveryPasswordMessage and @see validateRecoveryPasswordSecret )
     *
     *   - 'recipient' If need to extra STRENGTHEN validation
     *
     * @return void
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
}
