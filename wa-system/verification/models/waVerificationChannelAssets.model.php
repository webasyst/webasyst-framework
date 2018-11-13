<?php

class waVerificationChannelAssetsModel extends waModel
{
    protected $table = 'wa_verification_channel_assets';

    const NAME_SIGNUP_CONFIRM_HASH = 'signup_confirmation_hash';
    const NAME_SIGNUP_CONFIRM_CODE = 'signup_confirmation_code';
    const NAME_ONETIME_PASSWORD = 'onetime_password';
    const NAME_PASSWORD_RECOVERY_HASH = 'password_recovery_hash';
    const NAME_PASSWORD_RECOVERY_CODE = 'password_recovery_code';

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
        $channel_id = is_scalar($channel_id) ? (int)$channel_id : 0;
        if ($channel_id <= 0) {
            return false;
        }

        $address = is_scalar($address) ? (string)$address : '';
        if (strlen($address) <= 0) {
            return false;
        }

        $name = is_scalar($name) ? (string)$name : '';
        if (strlen($name) <= 0) {
            return false;
        }

        if (is_scalar($value)) {
            $value = (string)$value;
            if (strlen($value) <= 0) {
                return false;
            }
        } elseif (!$value) {
            return false;
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
                if ($ttl{0} === '-') {
                    return false;
                } elseif ($ttl{0} !== '+') {
                    $ttl = '+' . $ttl;
                }
                $time = strtotime($ttl);
                if ($time <= 0 || $time === false) {
                    return false;
                }
                $expires = date('Y-m-d H:i:s', $time);
            }
        }

        return $this->insert(array(
            'channel_id' => $channel_id,
            'address' => $address,
            'name' => $name,
            'value' => $value,
            'expires' => $expires
        ), 1);
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

    public function getById($value)
    {
        $this->clearExpired();  // for sure
        return parent::getById($value);
    }

    protected function clearExpired()
    {
        $now = date('Y-m-d H:i:s');
        $sql = "DELETE FROM `{$this->table}` WHERE `expires` <= :datetime";
        $this->exec($sql, array('datetime' => $now));
    }
}
