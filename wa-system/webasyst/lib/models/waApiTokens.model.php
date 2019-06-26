<?php

class waApiTokensModel extends waModel
{
    protected $id = 'token';
    protected $table = 'wa_api_tokens';

    public function getList($params = array(), &$total_count = null)
    {
        // LIMIT
        if (isset($params['offset']) || isset($params['limit'])) {
            $offset = (int) ifset($params['offset'], 0);
            $limit = (int) ifset($params['limit'], 50);
            if (!$limit) {
                return array();
            }
        } else {
            $offset = $limit = null;
        }

        if(!isset($params['count_results']) && func_num_args() > 1) {
            $params['count_results'] = true;
        }
        if (empty($params['count_results'])) {
            $select = "SELECT *";
        } else if ($params['count_results'] === 'only') {
            $select = "SELECT count(*)";
        } else {
            $select = "SELECT SQL_CALC_FOUND_ROWS *";
        }

        $sql = "{$select}
                FROM {$this->table}
                ORDER BY last_use_datetime DESC, create_datetime DESC, contact_id DESC";
        // LIMIT
        if ($limit) {
            $sql .= " LIMIT $offset, $limit";
        }

        $db_result = $this->query($sql);

        if (empty($params['count_results'])) {
            return $db_result->fetchAll('token');
        } elseif ($params['count_results'] === 'only') {
            $total_count = $db_result->fetchField();
            return $total_count;
        } else {
            $total_count = $this->query('SELECT FOUND_ROWS()')->fetchField();
            return $db_result->fetchAll('token');
        }
    }

    /**
     * @param string $client_id
     * @param int $contact_id
     * @param string $scope
     * @return string
     * @throws waException
     */
    public function getToken($client_id, $contact_id, $scope)
    {
        $row = $this->getByField(array('client_id' => $client_id, 'contact_id' => $contact_id));
        if ($row) {
            if ($row['scope'] != $scope) {
                $this->updateById($row['token'], array('scope' => $scope));
            }
            return $row['token'];
        } else {
            $token = $this->generateToken();
            $this->insert(array(
                'token' => $token,
                'client_id' => $client_id,
                'contact_id' => $contact_id,
                'scope' => $scope,
                'create_datetime' => date('Y-m-d H:i:s'),
                'expires' => null
            ));
            return $token;
        }
    }

    /**
     * Update last use datetime
     * @param array|string $token string token itself OR token record from DB
     */
    public function updateLastUseDatetime($token)
    {
        if (is_array($token) && isset($token['token'])) {
            $token = $token['token'];
        }
        if (!is_scalar($token)) {
            return;
        }
        $this->updateById($token, array(
            'last_use_datetime' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * @return string
     */
    protected function generateToken()
    {
        while (true) {
            $token = md5(uniqid().md5(microtime(true).uniqid()));
            // check the uniqueness of the token
            if (!$this->getById($token)) {
                return $token;
            }
        }
    }
}
