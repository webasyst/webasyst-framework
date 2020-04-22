<?php

class waVerificationChannelAssetsModel extends waModel
{
    protected $table = 'wa_verification_channel_assets';

    const NAME_SIGNUP_CONFIRM_HASH = 'signup_confirmation_hash';
    const NAME_SIGNUP_CONFIRM_CODE = 'signup_confirmation_code';
    const NAME_ONETIME_PASSWORD = 'onetime_password';
    const NAME_PASSWORD_RECOVERY_HASH = 'password_recovery_hash';
    const NAME_PASSWORD_RECOVERY_CODE = 'password_recovery_code';
    const NAME_CONFIRMATION_CODE = 'confirmation_code';

    public function __construct($type = null, $writable = false)
    {
        parent::__construct($type, $writable);
        $this->clearExpired();
    }

    /**
     * @param int $channel_id
     * @param string $address
     * @param string $name
     * @param string $value
     * @param null|int|string $ttl TTL
     *  if NULL, asset never expires,
     *  if int, number of seconds
     *  if string, than it pass to strtotime
     * @return bool
     */
    public function set($channel_id, $address, $name, $value, $ttl = null)
    {
        return $this->setAsset(array(
            'channel_id' => $channel_id,
            'address' => $address,
            'name' => $name,
            'value' => $value
        ), $ttl);
    }

    /**
     * @param array $data
     *   int 'channel_id'
     *   string 'address'
     *   int 'contact_id' (may be skipped)
     *   string 'name'
     *   string 'value'
     * @param null $ttl
     * @return bool|int|resource
     */
    public function setAsset($data = array(), $ttl = null)
    {
        // 'channel_id' is required
        $data['channel_id'] = isset($data['channel_id']) && wa_is_int($data['channel_id']) ? (int)$data['channel_id'] : 0;
        if ($data['channel_id'] <= 0) {
            return false;
        }

        // 'address' is required
        $data['address'] = isset($data['address']) && is_scalar($data['address']) ? (string)$data['address'] : '';
        if (strlen($data['address']) <= 0) {
            return false;
        }

        // 'contact_id' may be skipped
        if (isset($data['contact_id'])) {
            $data['contact_id'] = wa_is_int($data['contact_id']) ? (int)$data['contact_id'] : 0;
        }

        // 'name' is required
        $data['name'] = isset($data['name']) && is_scalar($data['name']) ? (string)$data['name'] : '';
        if (strlen($data['name']) <= 0) {
            return false;
        }

        // 'value' may be skipped
        if (isset($data['value'])) {
            $data['value'] =  is_scalar($data['value']) ? (string)$data['value'] : '';
            if (strlen($data['value']) <= 0) {
                return false;
            }
        }

        $expires = null;

        if ($ttl !== null) {
            if (wa_is_int($ttl)) {
                $ttl = (int)$ttl;
                if ($ttl <= 0) {
                    return false;
                }
                $expires = date('Y-m-d H:i:s', strtotime('+' . $ttl . ' seconds'));
            } elseif (is_scalar($ttl)) {
                $ttl = trim((string)$ttl);
                if ($ttl[0] === '-') {
                    return false;
                } elseif ($ttl[0] !== '+') {
                    $ttl = '+' . $ttl;
                }
                $time = strtotime($ttl);
                if ($time <= 0 || $time === false) {
                    return false;
                }
                $expires = date('Y-m-d H:i:s', $time);
            }
        }

        $data['tries'] = 0;
        $data['expires'] = $expires;

        return $this->insert($data, 1);

    }

    /**
     * Get and delete in once
     * @param $id
     * @return array|null
     */
    public function getOnce($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }
        $this->clearExpired();  // for sure
        $asset = $this->getById($id);
        if (!$asset) {
            return null;
        }
        $this->deleteById($asset['id']);
        return $asset;
    }

    /**
     * Get one asset by unique key (ID or other unique key)
     * Also increment 'tries' value
     * @param int|array $key
     * @return array|null
     * @throws waException
     */
    public function getAsset($key)
    {
        if (wa_is_int($key)) {

            // inc 'tries' field - table hold, so just one can process can inc this field
            $this->incTriesByWhere($this->getWhereByField(array('id' => $key)));

            // in this case get get already updated 'tires' counter
            $asset = $this->getById($key);

        } elseif (is_array($key)) {

            $field = array();

            // Build proper field (conditions) array to find asset by UNIQUE key
            // Order matters (for efficient search by using index)
            $fields = array('channel_id', 'address', 'contact_id', 'name');
            foreach ($fields as $field_id) {
                if (array_key_exists($field_id, $key)) {
                    $field[$field_id] = $key[$field_id];
                } elseif ($field_id === 'contact_id') {
                    $field['contact_id'] = 0;
                } else {
                    // not needed field_id
                    $field = array();
                    break;
                }
            }

            if (!$field) {
                // just any condition key
                $field = $key;
            }

            // inc 'tries' field - table hold, so just one can process can inc this field
            $this->incTriesByWhere($this->getWhereByField($field));

            // in this case get get already updated 'tires' counter
            $asset = $this->getByField($field);

        } else {
            $asset = null;
        }

        if (!$asset) {
            return null;
        }

        return $asset;
    }

    protected function incTriesByWhere($where)
    {
        $sql = "UPDATE `wa_verification_channel_assets` SET `tries` = `tries` + 1 WHERE {$where}";
        $this->exec($sql);
    }

    public function getByField($field, $value = null, $all = false, $limit = false)
    {
        $this->clearExpired();  // for sure
        return parent::getByField($field, $value, $all, $limit);
    }

    public function clearByContact($id)
    {
        $ids = waUtils::toIntArray($id);
        $ids = waUtils::dropNotPositive($ids);
        if (!$ids) {
            return;
        }

        $cem = new waContactEmailsModel();
        $emails = $cem->select('email')->where('contact_id IN(:ids)', array('ids' => $ids))->fetchAll(null, true);

        $cdm = new waContactDataModel();
        $phones = $cdm->select('value')->where('contact_id IN(:ids)', array('ids' => $ids))->fetchAll(null, true);

        $addresses = array_merge($emails, $phones);
        $addresses = array_unique($addresses);

        $this->deleteByField(array(
            'address' => $addresses,
        ));
    }

    protected function clearExpired()
    {
        $now = date('Y-m-d H:i:s');
        $sql = "DELETE FROM `{$this->table}` WHERE `expires` <= :datetime";
        $this->exec($sql, array('datetime' => $now));
    }
}
