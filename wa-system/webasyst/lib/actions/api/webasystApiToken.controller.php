<?php

class webasystApiTokenController extends waController
{

    protected $required_fields = array(
        'code' => true,
        'client_id' => true,
        'grant_type' => array('authorization_code')
    );

    public function execute()
    {
        if (!$this->checkRequest()) {
            return;
        }

        $code = waRequest::post('code');
        $auth_codes_model = new waApiAuthCodesModel();
        $row = $auth_codes_model->getById($code);

        if ($row) {
            // check client_id
            if ($row['client_id'] != waRequest::post('client_id')) {
                $this->response(array(
                    'error' => 'invalid_grant'
                ));
                return;
            }
            // check expire
            if (strtotime($row['expires']) < time()) {
                $this->response(array(
                    'error' => 'invalid_grant',
                    'error_description' => 'Authorization code has expired'
                ));
                return;
            }
            // create token
            $token_model = new waApiTokensModel();
            $token = $token_model->getToken($row['client_id'], $row['contact_id'], $row['scope']);
            $this->response(array('access_token' => $token));
        } else {
            $this->response(array(
                'error' => 'invalid_grant',
                'error_description' => 'Invalid code: '.$code
            ));
        }
    }


    protected function response($response)
    {
        if ($format = waRequest::get('format')) {
            $format = strtoupper($format);
            if (!in_array($format, array('JSON', 'XML'))) {
                $response = array(
                    'error' => 'invalid_request',
                    'error_description' => 'Invalid format: '.$format
                );
                $format = 'JSON';
            }
        }
        echo waAPIDecorator::factory($format)->decorate($response);
    }

    protected function checkRequest()
    {
        foreach ($this->required_fields as $field => $values) {
            $v = waRequest::post($field);
            if (!$v) {
                $this->response(array(
                    'error' => 'invalid_request',
                    'error_description' => 'Required parameter is missing: '.$field
                ));
                return false;
            }
            if (is_array($values) && !in_array($v, $values)) {
                $this->response(array(
                    'error' => ($field == 'grant_type' ? 'unsupported_grant_type' : 'invalid_request'),
                    'error_description' => 'Invalid '.$field.': '.$v
                ));
                return false;
            }
        }
        return true;
    }
}
