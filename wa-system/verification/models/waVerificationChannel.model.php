<?php

class waVerificationChannelModel extends waModel
{
    protected $table = 'wa_verification_channel';

    protected static $static_cache = array();

    const TYPE_SMS = 'sms';
    const TYPE_EMAIL = 'email';

    public static $available_types = array(self::TYPE_SMS, self::TYPE_EMAIL);

    public function __construct($type = null, $writable = false)
    {
        try {
            parent::__construct($type, $writable);
        } catch (waDbException $e) {
            // table not exist
            if ($e->getCode() == 1146) {
                $this->createTables();
                parent::__construct($type, $writable);
            } else {
                throw $e;
            }

        }

        // MUST BE ALWAYS AT LEAST ONE SYSTEM EMAIL CHANNEL, but check this condition only one time in runtime execution
        if (!isset(self::$static_cache['system_existing_checked'])) {
            $count = $this->countByField(array(
                'type' => waVerificationChannelModel::TYPE_EMAIL,
                'system' => 1
            ));
            if ($count > 0) {
                return;
            }
            $this->createDefaultSystemEmailChannel();
            self::$static_cache['system_existing_checked'] = true;
        }
    }

    /**
     * Try define system email for verification channel
     * @return array|mixed|string|null
     * @throws waDbException
     */
    protected function getDefaultSystemEmail()
    {
        $sm = new waAppSettingsModel();

        // Try get 'sender' email
        $email = $sm->get('webasyst', 'sender', '');
        $v = new waEmailValidator(array('required'=>true));
        if ($v->isValid($email)) {
            return $email;
        }

        $date = date('Y-m-d');
        waLog::log("System sender email is not valid or empty. Please correct it in \"Settings\" app => \"Email settings\" => \"Sender email address\"",
            "verification/channel/error-{$date}.log");

        return '';
    }

    protected function createDefaultSystemEmailChannel()
    {
        $email = $this->getDefaultSystemEmail();
        if (!$email) {
            return;
        }

        // Need be sure webasyst local is loaded
        // It is fine for performance though, this method call VERY rarely - only when table is clean
        waLocale::loadByDomain('webasyst', wa()->getLocale());

        $this->addChannel(array(
            'name' => _ws('System template'),
            'type' => self::TYPE_EMAIL,
            'address' => $email,
            'system' => 1
        ));
    }

    /**
     * @param array $data
     *   Required $data['type'] string (const TYPE_*)
     *   Required $data['address'] string
     * @return bool|int|resource
     * @throws waException
     */
    public function addChannel($data = array())
    {
        $types = $this->getTypes();
        if (!is_array($data)) {
            $data = array();
        }

        $data['type'] = isset($data['type']) ? $data['type'] : '';
        $this->typeMustExist($data['type']);

        if (empty($data['address'])) {
            throw new waException('Address is required for channel');
        }
        if (empty($data['name'])) {
            $data['name'] = sprintf(_ws('New channel %s'), $types[$data['type']]['name']);
        }
        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }

        $data['system'] = empty($data['system']) ? 0 : 1;

        $id = $this->insert($data);
        if (!$id) {
            return $id;
        }

        if (!empty($data['params']) && is_array($data['params'])) {
            $pm = new waVerificationChannelParamsModel();
            $pm->set($id, $data['params']);
        }

        return $id;
    }

    /**
     * @param int $id
     * @param array $data
     * @param bool $delete_old_params If $data['params'] exists this param will pass to set method
     * @see waVerificationChannelParamsModel::set()
     * @return bool|null|waDbResultUpdate
     */
    public function updateChannel($id, $data, $delete_old_params = true)
    {
        if (!is_array($data) || !wa_is_int($id) || $id <= 0) {
            return null;
        }

        // not-editable
        foreach (array('id', 'type', 'create_datetime') as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        $result = $this->updateById($id, $data);

        if (array_key_exists('params', $data) && (is_array($data['params']) || is_null($data['params']))) {
            $pm = new waVerificationChannelParamsModel();
            $pm->set($id, $data['params'], $delete_old_params);
        }

        return $result;
    }

    public function getChannel($id)
    {
        $channels = $this->getChannels(array($id));
        return isset($channels[$id]) ? $channels[$id] : null;
    }

    /**
     * @param null|int[] $ids If $ids missed (or null) than select all channels
     * @return array
     */
    public function getChannels($ids = null)
    {
        if ($ids !== null) {
            $ids = waUtils::toIntArray($ids);
            if (!$ids) {
                return array();
            }
        }
        // By specification 'email' type MUST be on FIRST PLACE
        // Just sort BY type work fine - but only for now
        $query = $this->select('*')->order('type,name,id');
        if ($ids) {
            $query->where('id IN(:ids)', array('ids' => $ids));
        }
        return $query->fetchAll('id');
    }

    public function getDefaultSystemEmailChannel()
    {
        $query = $this->select('*')
            ->order('id')
            ->where("type = :type AND `system` = 1", array('type' => self::TYPE_EMAIL))
            ->limit(1);
        $result = $query->fetchAssoc();
        if (!$result) {
            $this->createDefaultSystemEmailChannel();
        }
        $result = $query->fetchAssoc();
        return $result;
    }

    /**
     * @param string $type
     * @param bool|null $is_system
     * @return array all channels by type (sms or email)
     */
    public function getByType($type, $is_system = null) {
        if (!$this->typeExists($type)) {
            return array();
        }

        $where = array(
            'type = :type'
        );

        // mix-in system
        if ($is_system !== null) {
            $where[] = array(
                '`system` = :system'
            );
        } else {
            $is_system = $is_system ? 1 : 0;
        }

        $where = join(' AND ', $where);

        $query = $this->select('*')
                      ->order('`system` DESC, type DESC,name,id')
                      ->where($where, array('type' => $type, 'system' => $is_system));

        return $query->fetchAll('id');
    }

    public function deleteChannel($id)
    {
        $this->deleteChannels(array($id));
    }

    public function deleteChannels($ids)
    {
        $ids = waUtils::toIntArray($ids);
        $ids = waUtils::dropNotPositive($ids);
        if (!$ids) {
            return;
        }
        $pm = new waVerificationChannelParamsModel();
        $pm->deleteByField('channel_id', $ids);
        $this->deleteById($ids);
    }

    public function typeExists($type)
    {
        $type = is_scalar($type) ? (string)$type : '';
        $types = $this->getTypes();
        return isset($types[$type]);
    }

    /**
     * @param $type
     * @throws waException
     */
    public function typeMustExist($type)
    {
        if (!$this->typeExists($type)) {
            throw new waException('Unsupported verification channel type');
        }
    }

    public function isAddressUnique($type, $address, $except_id = null)
    {
        $where = "type = :type AND address = :address";
        $bind = array(
            'type' => $type,
            'address' => $address
        );
        if ($except_id > 0) {
            $where .= " AND id != :id";
            $bind['id'] = $except_id;
        }
        return !$this->select('id')->where($where, $bind)->fetchField();
    }

    public function getTypes($just_type_ids = false)
    {
        if (!isset(self::$static_cache['types'])) {
            self::$static_cache['types'] = array(
                self::TYPE_EMAIL => array(
                    'id' => self::TYPE_EMAIL,
                    'name' => _ws('Email'),
                    'icon' => 'email',
                    'description' => 'Email Verification Channel description'
                ),
                self::TYPE_SMS => array(
                    'id' => self::TYPE_SMS,
                    'name' => _ws('SMS'),
                    'icon' => 'phone',
                    'description' => 'SMS Verification Channel description'
                )
            );
        }
        return $just_type_ids ? array_keys(self::$static_cache['types']) : self::$static_cache['types'];
    }

    protected function createTables()
    {
        $tables = array('wa_verification_channel', 'wa_verification_channel_params', 'wa_verification_channel_assets');

        $db_path = wa()->getAppPath('lib/config/db.php', 'webasyst');
        $db = include($db_path);

        $db_partial = array();
        foreach ($tables as $table) {
            if (isset($db[$table])) {
                $db_partial[$table] = $db[$table];
            }
        }

        if (empty($db_partial)) {
            return;
        }

        $this->createSchema($db_partial);
    }
}
