<?php

/**
 * Update contact profile data on WAID profile update
 */
class webasystApiProfileUpdateController extends waController
{

    private $code;
    private $webasyst_contact_id;
    private $waid_client_id;

    public function execute()
    {
        
        $this->validateRequest();

        $cwm = new waContactWaidModel();
        $contact_id = $cwm->getBoundWithWebasystContact($this->webasyst_contact_id);
        if ($contact_id <= 0) {
            $this->response(null, 204);
        }

        $contact = new waContact($contact_id);
        $profile_update_info = (new waWebasystIDApi())->getProfileUpdated($contact, $this->code);

        if (empty($profile_update_info)) {
            $this->response(null, 204);
        }

        $parts = ifset($profile_update_info['parts']);
        if (empty($parts)) {
            $this->response(null, 204);
        }

        if (in_array('name', $parts)) {
            $this->updateContactNameByWaProfile($contact, $profile_update_info);
        }
        if (in_array('userpic', $parts) && !empty($profile_update_info['userpic_uploaded']) && !empty($profile_update_info['userpic_original_crop'])) {
            $this->saveUserpic($contact, $profile_update_info['userpic_original_crop']);
        }

        $this->response(null, 204);
    }

    protected function updateContactNameByWaProfile(waContact $contact, array $profile_info = [])
    {
        $update_data = [];
        $name_fields = ['firstname', 'lastname', 'middlename'];
        $is_not_empty = false;

        foreach ($name_fields as $name_field) {
            $update_data[$name_field] = isset($profile_info[$name_field]) ? $profile_info[$name_field] : '';
            if (!empty($update_data[$name_field])) {
                $is_not_empty = true;
            }
        }

        if ($is_not_empty) {
            $contact->save($update_data);
        }
    }

    protected function saveUserpic(waContact $contact, $photo_url)
    {
        // Load person photo and save to contact
        $photo = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($photo_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
            $photo = curl_exec($ch);
            curl_close($ch);
        } else {
            $scheme = parse_url($photo_url, PHP_URL_SCHEME);
            if (ini_get('allow_url_fopen') && in_array($scheme, stream_get_wrappers())) {
                $photo = @file_get_contents($photo_url);
            }
        }
        if ($photo) {
            $photo_url_parts = explode('/', $photo_url);
            $path = wa()->getTempPath('auth_photo/'.$contact->getId().'.'.md5(end($photo_url_parts)), 'webasyst');
            file_put_contents($path, $photo);
            try {
                $contact->setPhoto($path);
            } catch (Exception $exception) {
                
            }
        }
    }

    protected function validateRequest()
    {
        $method = $this->getRequest()->method();
        if ($method != waRequest::METHOD_POST) {
            $this->response([
                'error' => 'unsupported_method',
                'error_description' => sprintf('Method "%s" is not supported', $method),
            ], 400);
        }

        $this->webasyst_contact_id = waRequest::post('waid', 0, waRequest::TYPE_INT);
        if ($this->webasyst_contact_id <= 0) {
            $this->response([
                'error' => 'invalid_request',
                'error_description' => 'Webasyst ID must be provided',
            ], 400);
        }

        $this->code = waRequest::post('code');
        if (empty($this->code)) {
            $this->response([
                'error' => 'invalid_request',
                'error_description' => 'Update code must be provided',
            ], 400);
        }

        $this->waid_client_id = waRequest::post('client_id');
        if (empty($this->waid_client_id)) {
            $this->response([
                'error' => 'invalid_request',
                'error_description' => 'Webasyst client ID must be provided',
            ], 400);
        }

        $m = new waWebasystIDClientManager();
        $credentials = $m->getCredentials();
        if (empty($credentials) || $this->waid_client_id != $credentials['client_id']) {
            $this->response([
                'error' => 'client_id_not_found',
                'error_description' => 'Webasyst client ID not found',
            ], 404);
        }
    }

    protected function response($response, $status_code = 200)
    {
        wa()->getResponse()
            ->addHeader('Content-Type', 'application/json')
            ->setStatus($status_code)
            ->sendHeaders();

        if ($status_code == 204) {
            exit;
        }
        die(waAPIDecorator::factory('JSON')->decorate($response));
    }
}
