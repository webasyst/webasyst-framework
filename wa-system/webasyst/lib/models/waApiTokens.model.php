<?php 

class waApiTokensModel extends waModel
{
    protected $id = 'token';
    protected $table = 'wa_api_tokens';

    /**
     * @param string $client_id
     * @param int $contact_id
     * @param string $scope
     * @return string
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