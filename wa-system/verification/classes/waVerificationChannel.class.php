<?php

abstract class waVerificationChannel
{
    protected static $static_cache = array();

    protected $id;
    protected $type;
    protected $info;
    protected $options;

    const VERIFY_ERROR_INVALID = 'invalid';
    const VERIFY_ERROR_OUT_OF_TRIES = 'out_of_tries';

    /**
     * waVerificationChannel constructor.
     * @param $channel
     * @param array $options
     * @throws waException
     */
    public function __construct($channel, $options = array())
    {
        $this->options = is_array($options) ? $options : array();
        if (wa_is_int($channel)) {
            $this->id = (int)$channel;
        } elseif (is_array($channel) && isset($channel['id']) && isset($channel['type'])) {
            $this->info = $channel;
            $this->id = (int)$channel['id'];
            $this->type = $channel['type'];
            self::getModel()->typeMustExist($this->type);
        } elseif (is_string($channel)) {
            $this->type = $channel;
            self::getModel()->typeMustExist($this->type);
        } else {
            throw new waException('Invalid construct param {$channel}');
        }
    }

    /**
     * @param waVerificationChannel|int|array|string|null $channel
     * @param array $options
     * @return waVerificationChannel
     */
    public static function factory($channel, $options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        if ($channel instanceof waVerificationChannel) {
            return $channel;
        } elseif (wa_is_int($channel)) {
            $type = self::getModel()->select('type')->where('id = ?', (int)$channel)->fetchField();
        } elseif (is_array($channel) && isset($channel['id']) && isset($channel['type'])) {
            $type = $channel['type'];
        } elseif (is_string($channel)) {
            $type = $channel;
        } else {
            $type = null;
        }

        $instance = null;

        if ($type === waVerificationChannelModel::TYPE_EMAIL) {
            try {
                $instance = new waVerificationChannelEmail($channel, $options);
            } catch (waException $e) {
            }
        } elseif ($type === waVerificationChannelModel::TYPE_SMS) {
            try {
                $instance = new waVerificationChannelSMS($channel, $options);
            } catch (waException $e) {
            }
        }

        return $instance ? $instance : new waVerificationChannelNull();
    }

    /**
     * @return int
     */
    public function getId()
    {
        if ($this->id !== null) {
            return $this->id;
        }
        $info = $this->getInfo();
        return $info['id'];
    }

    /**
     * @return bool
     */
    public function isEmail()
    {
        return $this instanceof waVerificationChannelEmail;
    }

    /**
     * @return bool
     */
    public function isSMS()
    {
        return $this instanceof waVerificationChannelSMS;
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return $this instanceof waVerificationChannelNull;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        $info = $this->getInfo();
        return $info['id'] > 0;
    }

    /**
     * @param bool $all With params or not
     * @return array Info storage in DB
     *   If $all == TRUE also 'params' key with array of params in key-value format
     */
    public function getInfo($all = true)
    {
        if ($this->info == null) {
            $this->info = $this->loadInfo($this->id);
            $this->id = (int)$this->info['id'];
        }

        if ($all && !isset($this->info['params'])) {
            $this->info['params'] = $this->loadParams($this->id);
        }

        $info = $this->info;

        if (!$all && isset($info['params'])) {
            unset($info['params']);
        }

        return $info;
    }

    /**
     * Get default template(s)
     * @param null $name If null return all templates
     * @param null $loc If null use current user locale
     *
     * Be careful with formats
     * For each channel result format is different
     *
     * @see waVerificationChannelEmail::getDefaultTemplates()
     * @see waVerificationChannelSMS::getDefaultTemplates()
     *
     * @return mixed
     *   - string|array - One template May be string, may be array (see email channel)
     *   - array Array of format name => <template>, where <template> is string|array (see email channel)
     */
    public function getDefaultTemplates($name = null, $loc = null)
    {
        $loc = $loc ? $loc : wa()->getUser()->getLocale();
        $type = $this->getType();
        if (!isset(self::$static_cache['default_templates'][$type][$loc])) {
            self::$static_cache['default_templates'][$type][$loc] = $this->loadDefaultTemplates($loc);
        }
        return $name !== null ? (string)ifset(self::$static_cache['default_templates'][$type][$loc][$name]) :
                    self::$static_cache['default_templates'][$type][$loc];
    }

    protected function loadDefaultTemplates($loc)
    {
        $dir = waConfig::get('wa_path_system') . '/verification/default_templates/';
        $path = $dir . $this->getType() . '/' . $loc . '/';
        if (!file_exists($path)) {
            return array();
        }
        $templates = array();
        foreach (waFiles::listdir($path) as $file_name) {
            $template_id = str_replace('.html', '', $file_name);
            $templates[$template_id] = trim(file_get_contents($path . $file_name));
        }
        return $templates;
    }

    public function getParams()
    {
        $this->getInfo(true);
        return $this->info['params'];
    }

    public function getParam($name, $default = null)
    {
        if (!is_scalar($name)) {
            return $default;
        }
        $params = $this->getParams();
        return isset($params[$name]) ? $params[$name] : $default;
    }

    public function getTemplate($name)
    {
        if (!is_scalar($name)) {
            return null;
        }
        $templates = $this->getParam('template');
        $templates = is_array($templates) ? $templates : array();
        if (isset($templates[$name])) {
            return $templates[$name];
        } else {
            return $this->getDefaultTemplates($name);
        }
    }

    public function setTemplate($name, $value)
    {
        if (array_key_exists($name, $this->getTemplatesList())) {
            $templates = $this->getParam('template');
            $templates = is_array($templates) ? $templates : array();
            if (empty($templates)) {
                $templates = $this->getDefaultTemplates();
            }
            $templates[$name] = $value;
            $this->setParam('template', $templates);
        }
    }

    public function getName()
    {
        $info = $this->getInfo();
        return $info['name'];
    }

    public function getAddress()
    {
        $info = $this->getInfo();
        return $info['address'];
    }

    public function getType()
    {
        if ($this->type) {
            return $this->type;
        }
        $info = $this->getInfo();
        return $this->type = $info['type'];
    }

    /**
     * Runtime setter
     * @param $name
     */
    public function setName($name)
    {
        $name = is_scalar($name) ? (string)$name : '';
        $this->getInfo();
        $this->info['name'] = $name;
    }

    /**
     * Runtime setter
     * @param $address
     */
    public function setAddress($address)
    {
        $address = is_scalar($address) ? (string)$address : '';
        $this->getInfo();
        $this->info['address'] = $address;
    }

    /**
     * @param int|bool $value
     */
    public function setSystem($value)
    {
        $this->getInfo();
        $this->info['system'] = empty($value) ? '0' : '1';
    }

    /**
     * @return bool
     */
    public function isSystem()
    {
        $this->getInfo();
        return $this->info['system'] > 0;
    }

    /**
     * Runtime setter
     * @param string $name
     * @param mixed $value
     */
    public function setParam($name, $value)
    {
        $this->getParams();
        if (is_scalar($name)) {
            $this->info['params'][$name] = $value;
        }
    }

    /**
     * Runtime setter
     * @param array[string]mixed $params
     * @param bool $unset_old_params
     */
    public function setParams($params, $unset_old_params = true)
    {
        if (!is_array($params)) {
            return;
        }
        $this->getParams();
        if ($unset_old_params) {
            $this->info['params'] = $params;
        } else {
            foreach ($params as $name => $val) {
                $this->info['params'][$name] = $val;
            }
        }
    }

    /**
     * Save info into DB
     * @example
     * $ch->setParam('key_1', 'value_1');
     * $ch->setParam('key_2', 'value_2');
     * $ch->setName('super');
     * ...
     * $ch->commit()
     *
     */
    public function commit()
    {
        // save fresh info
        $this->getInfo();
        $this->getParams();
        $info = $this->info;
        // reset info, so we load in 'save' DB variant of data
        $this->info = null;
        $this->save($info, true);
    }

    /**
     * Save (update or insert) date into DB
     * @param array $data array of fields of records + 'params' key for save params
     * @param bool $delete_old_params Delete old param values or not
     * @throws waException
     */
    public function save($data, $delete_old_params = false)
    {
        if (!$data && !is_array($data)) {
            return;
        }

        $info = $this->getInfo();
        $info_params = $info['params'];
        unset($info['params']);

        $data_params = (array)ifset($data['params']);
        $data['params'] = $data_params;
        unset($data['params']);

        $data = array_merge($info, $data);

        if (!$delete_old_params) {
            $data_params = array_merge($info_params, $data_params);
        }

        $data['params'] = $data_params;

        if (!$this->exists()) {
            $this->id = self::getModel()->addChannel($data);
        } else {
            self::getModel()->updateChannel($this->id, $data, $delete_old_params);
        }

        $this->info = null;
    }

    /**
     * @param array[string]mixed $params
     * @param bool $delete_old_params
     */
    public function saveParams($params, $delete_old_params = true)
    {
        if (!is_array($params)) {
            return;
        }
        try {
            $this->save(array('params' => $params), $delete_old_params);
        } catch (waException $e) {
            //
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function saveParam($key, $value)
    {
        if (is_scalar($key)) {
            try {
                $this->save(array('params' => array($key => $value)));
            } catch (waException $e) {

            }
        }
    }

    /**
     * Unset all params in runtime, not in DB
     */
    public function unsetParams()
    {
        if ($this->info) {
            $this->info['params'] = array();
        }
    }

    /**
     * Unset param in runtime, not in DB
     * @param string $name
     */
    public function unsetParam($name)
    {
        if (!is_scalar($name) || !$this->info) {
            return;
        }
        if (array_key_exists($name, $this->info['params'])) {
            unset($this->info['params'][$name]);
        }
    }

    /**
     * Delete params in DB
     */
    public function deleteParams()
    {
        if ($this->exists()) {
            $this->saveParams(array());
        } else {
            $this->unsetParams();
        }
    }

    /**
     * Delete param in DB
     * @param string $key
     */
    public function deleteParam($key)
    {
        if (!is_scalar($key)) {
            return;
        }
        if ($this->exists()) {
            $this->saveParams(array($key => null), false);
        } else {
            $this->unsetParam($key);
        }
    }

    /**
     * Delete channel record form DB
     */
    public function delete()
    {
        if (!$this->exists()) {
            return;
        }
        self::getModel()->deleteChannel($this->id);
        $this->info = null;
    }

    protected function generateCode()
    {
        $len = 4;
        $code = array();
        for ($i = 0; $i < $len; $i++) {
            $code[] = rand(0, 9);
        }
        return join('', $code);
    }

    protected function generateOnetimePassword()
    {
        $len = 4;
        $code = array();
        for ($i = 0; $i < $len; $i++) {
            $code[] = rand(0, 9);
        }
        return join('', $code);
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
     *   Options depends on concrete implementation of method
     *
     * @return mixed
     */
    abstract public function sendSignUpConfirmationMessage($recipient, $options = array());

    /**
     *
     * Has been sent confirmation message for signup
     *
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options For feature use
     * @return bool
     */
    public function hasSentSignUpConfirmationMessage($recipient, $options = array())
    {
        $recipient = $this->typecastInputRecipient($recipient);
        if (!$recipient) {
            return false;
        }

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getAsset(array(
            'channel_id' => $this->getId(),
            'address' => $recipient['email'],
            'contact_id' => isset($recipient['id']) ? $recipient['id'] : 0,
            'name' => waVerificationChannelAssetsModel::NAME_SIGNUP_CONFIRM_HASH
        ));

        return (bool)$asset;
    }

    /**
     * @param string $confirmation_secret
     * @param array $options
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
     *
     */
    abstract public function validateSignUpConfirmation($confirmation_secret, $options = array());

    /**
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options Depends on concrete implementation of method
     * @return mixed
     */
    abstract public function sendSignUpSuccessNotification($recipient, $options = array());

    /**
     * TODO: Make explicitly passing $onetime_password to method by second parameter
     *
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options
     *   Options depends on concrete implementation of method
     *
     * @return mixed
     */
    abstract public function sendOnetimePasswordMessage($recipient, $options = array());

    /**
     * @param string $password
     * @param array $options
     *   - 'recipient' If need to extra STRENGTHEN validation
     *   - 'asset_id' Optional. Asset ID where is stored onetime password. If skipped, get asset from session
     *
     * @return bool|string
     *   Return address that was validated OR FALSE (if was failure)
     */
    public function validateOnetimePassword($password, $options = array())
    {
        $use_session = !isset($options['asset_id']);

        $session_key = null;

        if ($use_session) {
            $session_key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_ONETIME_PASSWORD;
            $asset_id = wa()->getStorage()->get($session_key);
            $asset_id = wa_is_int($asset_id) ? (int)$asset_id : 0;
        } else {
            $asset_id = wa_is_int($options['asset_id']) ? (int)$options['asset_id'] : 0;
        }

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getById($asset_id);
        if (!$asset) {
            return false;
        }

        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_ONETIME_PASSWORD) {
            return false;
        }

        $recipient = null;
        if (isset($options['recipient'])) {
            $recipient = $this->typecastInputRecipient($options['recipient']);
        }

        if ($recipient !== null && $asset['address'] !== $recipient['address']) {
            return false;
        }

        if (waContact::getPasswordHash($password) !== $asset['value']) {
            return false;
        }

        // clean asset
        $vca->deleteById($asset['id']);
        if ($use_session) {
            wa()->getStorage()->del($session_key);
        }

        return $asset['address'];
    }

    /**
     * Send message with confirmation code
     * By this code recipient can confirm that current channel (address) belongs to this recipient
     *
     * TODO: Maybe need take into account timeout for sending right inside of this method? I do not now yet
     *
     * @param $recipient
     * @param array $options
     * @return mixed
     */
    abstract public function sendConfirmationCodeMessage($recipient, $options = array());

    /**
     * @param string|int $code
     * @param array $options
     *
     *   - 'recipient' If need to extra STRENGTHEN validation
     *   - 'asset_id' Optional. Asset ID where is stored onetime password. If skipped, get asset from session
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
     *        If 'status' is TRUE 'details' has keys:
     *          - string 'address'     - address that was validated
     *          - int    'contact_id'  - id of contact bind to this address
     *        Otherwise 'details' has keys:
     *          - string 'error' - string identificator of error - VERIFY_ERROR_* consts
     * @throws waException
     */
    public function validateConfirmationCode($code, $options = array())
    {
        // Initialize fail result structure
        $fail = array(
            'status' => false,
            'details' => array(
                'error' => self::VERIFY_ERROR_INVALID
            )
        );

        $use_session = !isset($options['asset_id']);

        $session_key = null;

        if ($use_session) {
            $session_key = 'wa_verification_channel_' . $this->getId() . '_asset/' . waVerificationChannelAssetsModel::NAME_CONFIRMATION_CODE;
            $asset_id = wa()->getStorage()->get($session_key);
            $asset_id = wa_is_int($asset_id) ? (int)$asset_id : 0;
        } else {
            $asset_id = wa_is_int($options['asset_id']) ? (int)$options['asset_id'] : 0;
        }

        $vca = new waVerificationChannelAssetsModel();
        $asset = $vca->getAsset($asset_id);
        if (!$asset) {
            return $fail;
        }

        // check tries logic
        if (isset($options['check_tries']) && is_array($options['check_tries'])) {
            // number of tries
            $tries = isset($options['check_tries']['count']) ? $options['check_tries']['count'] : null;

            // if exceeding number of tries
            if (wa_is_int($tries) && $asset['tries'] > $tries) {

                // set proper error
                $fail['details']['error'] = self::VERIFY_ERROR_OUT_OF_TRIES;

                // need clean asset, so next call of this method returns VERIFY_ERROR_INVALID error
                if (!empty($options['check_tries']['clean'])) {
                    $vca->deleteById($asset['id']);
                    if ($use_session) {
                        wa()->getStorage()->del($session_key);
                    }
                }

                return $fail;
            }
        }

        // check is proper asset
        if ($asset['channel_id'] != $this->getId() ||
            $asset['name'] != waVerificationChannelAssetsModel::NAME_CONFIRMATION_CODE) {
            return $fail;
        }

        $recipient = null;
        if (isset($options['recipient'])) {
            $recipient = $this->typecastInputRecipient($options['recipient']);
        }

        $asset_contact_id = (int)$asset['contact_id'];

        if ($recipient !== null) {

            // check addresses
            if ($asset['address'] !== $recipient['address']) {
                return $fail;
            }

            // check contact IDs
            if (isset($recipient['id']) && intval($recipient['id']) !== $asset_contact_id) {
                return $fail;
            }

        }

        // check code
        if (waContact::getPasswordHash($code) !== $asset['value']) {
            return $fail;
        }

        // clean asset
        $vca->deleteById($asset['id']);
        if ($use_session) {
            wa()->getStorage()->del($session_key);
        }

        // SUCCESS
        return array(
            'status' => true,
            'details' => array(
                'address' => $asset['address'],
                'contact_id' => $asset_contact_id
            )
        );

    }

    /**
     * Send message where will be secret that grants rights to recovery (set new) password
     *
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options Depends on concrete implementation of method
     * @return mixed
     */
    abstract public function sendRecoveryPasswordMessage($recipient, $options = array());

    /**
     * Validate secret that was in message
     * That secret grants rights to recovery (set new) password
     *
     * @see sendRecoveryPasswordMessage
     * @param string $secret
     * @param array $options Depends on concrete implementation of method
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
    abstract public function validateRecoveryPasswordSecret($secret, $options = array());

    /**
     * Invalidate secret that grants rights to recovery (sen new) password
     * Call when no longer need to keep it (secret)
     *
     * @param string $secret
     * @param array $options Depends on concrete implementation of method
     * @return void
     */
    abstract public function invalidateRecoveryPasswordSecret($secret, $options = array());

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
     *   Options depends on concrete implementation of method
     *
     * @return mixed
     */
    abstract public function sendPassword($recipient, $password, $options = array());

    /**
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     * @return null|array
     *   + 'address' Required
     *   + other optional fields
     */
    abstract protected function typecastInputRecipient($recipient);

    abstract public function isWorking();

    /**
     * Keep track stats about sending
     * @param bool $status
     */
    protected function trackSendingStats($status)
    {
        // For existing channel KEEP TRACK fail stats

        $send_stats = $this->getSendingStats();

        $send_stats['total'] += 1;
        $send_stats['last_send_dt'] = date('Y-m-d H:i:s');

        if (!$status) {
            $send_stats['total_failed'] += 1;
            $send_stats['last_failed']  += 1;
            $send_stats['last_fail_dt'] = date('Y-m-d H:i:s');
        } else {
            $send_stats['last_failed'] = 0;
        }

        if ($this->exists()) {
            $this->saveParam('send_stats', $send_stats);
        } else {
            $this->setParam('send_stats', $send_stats);
        }

    }

    /**
     * @return array
     *  - int           'total'         total sends
     *  - int           'total_failed'  total number of failed sends
     *  - int           'last_failed'   number only of last failed in a row sends
     *  - null|string   'last_send_dt'  datetime of last send
     *  - null|string   'last_fail_dt'  datetime of last failed send
     */
    protected function getSendingStats()
    {
        $stats = array(
            'total'        => 0,
            'total_failed' => 0,
            'last_failed'  => 0,
            'last_send_dt'    => null,
            'last_fail_dt'    => null,
        );

        $send_stats = $this->getParam('send_stats');
        $send_stats = is_array($send_stats) ? $send_stats : array();

        // merge with defaults and ensure all keys we needed
        $send_stats = array_merge($stats, $send_stats);

        // typecast each field
        foreach ($send_stats as $key => &$value) {
            if (!array_key_exists($key, $stats)) {
                unset($send_stats[$key]);
            } elseif (wa_is_int($stats[$key])){
                $value = wa_is_int($value) && $value >= 0 ? $value : 0;
            } else {
                $value = is_scalar($value) ? $value : null;
            }
        }
        unset($value);

        return $send_stats;
    }

    /**
     * Render template
     * @param string $template It's template itself - not path to template
     * @param array $assign key-value assign for template
     * @param bool $auto_escape
     * @return string
     */
    protected function renderTemplate($template, $assign = array(), $auto_escape = true)
    {
        $template = is_scalar($template) ? trim((string)$template) : '';
        if (strlen($template) <= 0) {
            return '';
        }
        $view = wa()->getView();
        $old_vars = $view->getVars();
        $view->clearAllAssign();

        // TODO: in future move list of vars that need to escape in protected property (or somewhere else)
        if ($auto_escape) {
            if (isset($assign['site_name'])) {
                $assign['site_name'] = htmlspecialchars($assign['site_name']);
            }
        }

        $view->assign($assign);
        $result = $view->fetch('string:'.$template);
        $view->clearAllAssign();
        $view->assign($old_vars);
        return $result;
    }

    protected function loadInfo($id)
    {
        $channel = null;
        if ($id) {
            $channel = self::getModel()->getChannel($id);
        }
        if (!$channel) {
            $channel = self::getModel()->getEmptyRow();
            $channel['type'] = $this->type;
        }
        return $channel;
    }

    protected function loadParams($id)
    {
        return self::getParamsModel()->get($id);
    }

    /**
     * @param $type
     * @throws waException
     */
    protected function typeMustConsistent($type)
    {
        if ($this->type !== $type) {
            $msg = 'Type inconsistent. Must be %s';
            throw new waException(sprintf($msg, $type));
        }

    }

    protected function logException(Exception $e)
    {
        $file = 'verification/exceptions.log';
        $message = array(
            sprintf('Channel of type %s with ID = %d triggers exception: ', $this->getType(), (int)$this->getId()),
            $e->getMessage(),
            $e->getTraceAsString()
        );
        $message = join(PHP_EOL, $message);
        waLog::log($message, $file);
    }

    /**
     * @return waVerificationChannelModel
     */
    protected static function getModel()
    {
        if (!isset(self::$static_cache['models']['main'])) {
            self::$static_cache['models']['main'] = new waVerificationChannelModel();
        }
        return self::$static_cache['models']['main'];
    }

    /**
     * @return waVerificationChannelParamsModel
     * @throws waException
     */
    protected static function getParamsModel()
    {
        if (!isset(self::$static_cache['models']['params'])) {
            self::$static_cache['models']['params'] = new waVerificationChannelParamsModel();
        }
        return self::$static_cache['models']['params'];
    }

    public function getTemplatesList()
    {
        $templates_list = array(

            // Template of message that is notification about successful signing up
            // Also can has generated password
            'successful_signup' => _ws('Successful signup'),

            // Template of message that has instruction about sign up confirmation
            'confirm_signup'    => _ws('Signup confirmation'),

            // Template of message that has temporary secure link of page where client can set new password
            'recovery_password' => _ws('Password recovery'),

            // Template of message that has new generated password (as a result of password recovery procedure)
            'password'          => _ws('Password'),

            // Template of message that has onetime password for logging in
            'onetime_password'  => _ws('One-time password'),

            // Template of message that has confirmation code to confirm channel
            'confirmation_code' => _ws('Confirmation code')
        );
        return $templates_list;
    }

    /**
     * Get vars name for each predefined template, optionally with description
     * @param string $template_name
     * @param bool $with_description
     * @return array
     */
    abstract public function getTemplateVars($template_name, $with_description = false);

    /**
     * Get string represent diagnostic info about channel
     * @param bool $verbose - if verbose add sending stats info
     * @return string
     */
    public function getDiagnostic($verbose = true)
    {
        // IMPORTANT:
        // @var_export - @ just in case if var_export trigger warning or notice
        // For example "var_export does not handle circular references"

        $msg = "Channel diagnostic:\nType: %s\nInfo: %s";
        $msg = sprintf($msg, get_class($this), @var_export($this->getInfo(false), true));
        if ($verbose) {
            $msg .= sprintf("\nStats:%s", @var_export($this->getSendingStats(), true));
        }
        return $msg;
    }
}
