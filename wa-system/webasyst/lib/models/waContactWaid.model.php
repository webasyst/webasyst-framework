<?php

/**
 * Class waContactWaidModel
 *
 * Keep relation between contact and webasyst contact ID
 * Also storage for webasyst ID token params
 */
class waContactWaidModel extends waModel
{
    protected $table = 'wa_contact_waid';
    protected $id = 'contact_id';

    /**
     * Bind contact id with webasyst contact ID and save token params
     * @param int $contact_id
     * @param int $webasyst_contact_id
     * @param array $token_params - token params
     *      - string $token_params['access_token']  [required] - access token itself (jwt)
     *      - string $token_params['refresh_token'] [optional] - refresh token to refresh access token
     *      - int    $token_params['expires_in']    [optional] - ttl of expiration in seconds
     *      - string $token_params['token_type']    [optional] - "bearer"
     * @throws waException
     */
    public function set($contact_id, $webasyst_contact_id, $token_params)
    {
        $data = [
            'webasyst_contact_id' => $webasyst_contact_id,
            'token' => json_encode($token_params),
        ];

        // 'webasyst_contact_id' is unique, old binding must be deleted
        if ($this->getByField(['webasyst_contact_id' => $webasyst_contact_id])) {
            $this->deleteByField(['webasyst_contact_id' => $webasyst_contact_id]);
        }

        $data['create_datetime'] = date('Y-m-d H:i:s');

        if ($this->getById($contact_id)) {
            $this->updateById($contact_id, $data);
        } else {
            $data['contact_id'] = $contact_id;
            $this->insert($data);
        }
    }

    /**
     * @param $contact_id
     * @return array|null $result
     *      array|null $result['token'] - already unserilized token params (not string from DB)
     */
    public function get($contact_id)
    {
        $data = $this->getById($contact_id);
        if (!$data) {
            return null;
        }
        $data['token'] = $this->unserializeTokenParams($data['token']);
        return $data;
    }

    /**
     * @param int $contact_id
     * @param array $token_params - token params
     *      - string $token_params['access_token']  [required] - access token itself (jwt)
     *      - string $token_params['refresh_token'] [optional] - refresh token to refresh access token
     *      - int    $token_params['expires_in']    [optional] - ttl of expiration in seconds
     *      - string $token_params['token_type']    [optional] - "bearer"
     * @throws waException
     */
    public function updateToken($contact_id, $token_params)
    {
        $this->updateById($contact_id, [
            'token' => json_encode($token_params),
        ]);
    }

    public function del($contact_id)
    {
        $this->deleteById($contact_id);
    }

    /**
     * Get contact bound with this webasyst ID contact
     * @param int $webasyst_contact_id
     * @param int|int[] $exclude_contact_ids - that contacts among which no need searching
     * @return int - found contact id or 0
     * @throws waException
     */
    public function getBoundWithWebasystContact($webasyst_contact_id, $exclude_contact_ids = [])
    {
        $where = $this->getWhereByField(['webasyst_contact_id' => $webasyst_contact_id]);
        $bind_params = [];

        // no search among that list of contact ids
        if ($exclude_contact_ids) {
            $exclude_contact_ids = waUtils::toIntArray($exclude_contact_ids);
            $exclude_contact_ids = waUtils::dropNotPositive($exclude_contact_ids);
            $where .= " AND contact_id NOT IN(:ids)";
            $bind_params['ids'] = $exclude_contact_ids;
        }

        $contact_id = $this->select('contact_id')->where($where, $bind_params)->fetchField();
        return intval($contact_id);
    }

    /**
     * Clear all webasyst ID bounds along with token params
     */
    public function clearAll()
    {
        $this->exec("DELETE FROM `{$this->table}` WHERE 1");
    }

    /**
     * Clear webasyst ID bounds along with token params
     * @param array $contact_ids
     */
    public function clear(array $contact_ids)
    {
        $this->deleteById($contact_ids);
    }

    /**
     * Unserialize token params string - json encode and typecast
     * @param string $params_str
     * @return null|array $params - if token params saved is not valid by expected format OR not existed at all returns NULL, otherwise:
     *      - string $params['access_token']  [required] - access token itself (jwt), if there is not this field returns NULL
     *      - string $params['refresh_token'] [optional] - refresh token to refresh access token, if not valid return ''
     *      - int    $params['expires_in']    [optional] - ttl of expiration in seconds, if not valid return 0
     *      - string $params['token_type']    [optional] - "bearer", always return "bearer"
     */
    protected function unserializeTokenParams($params_str)
    {
        $params = json_decode($params_str, true);
        if (!$params || !is_array($params)) {
            return null;
        }

        if (!isset($params['access_token']) || !is_scalar($params['access_token'])) {
            return null;
        }

        $expires_in = 0;
        if (isset($params['expires_in']) && wa_is_int($params['expires_in']) > 0 && $params['expires_in'] > 0) {
            $expires_in = intval($params['expires_in']);
        }

        $refresh_token = '';
        if (isset($params['refresh_token']) && is_scalar($params['refresh_token'])) {
            $refresh_token = $params['refresh_token'];
        }

        return [
            'access_token' => $params['access_token'],
            'expires_in' => $expires_in,
            'refresh_token' => $refresh_token,
            'token_type' => "bearer"
        ];
    }

}
