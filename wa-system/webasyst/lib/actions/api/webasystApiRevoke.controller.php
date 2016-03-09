<?php

class webasystApiRevokeController extends waController
{
    public function execute()
    {
        $token = waRequest::request('access_token', '', 'string');

        $token_model = new waApiTokensModel();
        $token_model->deleteById($token);

        $this->response(array('access_token' => $token));
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
}