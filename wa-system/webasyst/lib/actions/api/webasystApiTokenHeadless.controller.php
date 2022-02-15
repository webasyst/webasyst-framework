<?php

class webasystApiTokenHeadlessController extends waController
{
    protected $required_fields = array(
        'code' => true,
        'client_id' => true,
        'scope' => true,
    );

    /**
     * @var waWebasystIDClientManager
     */
    protected $client_manager;
    protected $url_provider;

    public function __construct()
    {
        $this->client_manager = new waWebasystIDClientManager();
        $this->url_provider = new waWebasystIDUrlsProvider();
    }

    public function execute()
    {
        $this->corsWorkaround();

        if (!$this->checkRequest()) {
            return;
        }

        $scope = $this->getRequest()->post('scope');
        $code = $this->getRequest()->post('code');

        $result = $this->getAccessToken($code, $scope);
        if (isset($result['error'])) {
            $this->response($this->dropKeys($result, ['status_code']), $result['status_code']);
            return;
        }

        $contact = $this->getBoundBackendUser($result);
        if (!$contact) {
            $this->response([
                'error' => 'invalid_grant',
                'error_description' => 'No backend user is linked with this Webasyst ID',
            ], 403);
            return;
        }

        $token = $this->createApiToken($contact->getId());
        $this->response(array('access_token' => $token));
    }

    protected function createApiToken($contact_id)
    {
        $client_id = $this->getRequest()->post('client_id');
        $scope = $this->getRequest()->post('scope');

        $token_model = new waApiTokensModel();
        return $token_model->getToken($client_id, $contact_id, $scope);
    }

    /**
     * @param array $params
     * @return waContact|null
     * @throws waException
     */
    protected function getBoundBackendUser(array $params)
    {
        // Extract Webasyst contact
        $m = new waWebasystIDAccessTokenManager();
        $token_info = $m->extractTokenInfo($params['access_token']);
        $contact_id = $token_info['contact_id'];

        // Found contact that already bound with this Webasyst contact
        $cwm = new waContactWaidModel();
        $bound_contact_id = $cwm->getBoundWithWebasystContact($contact_id);
        $bound_contact = new waContact($bound_contact_id);

        if (!$bound_contact['is_user']) {
            return null;
        }

        return $bound_contact;
    }

    protected function response($response, $status_code = 200)
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
        $content_type = ($format === 'XML') ? 'application/xml' : 'application/json';
        wa()->getResponse()
            ->addHeader('Content-Type', $content_type)
            ->setStatus($status_code)
            ->sendHeaders();
        die(waAPIDecorator::factory($format)->decorate($response));
    }

    protected function checkRequest()
    {
        foreach ($this->required_fields as $field => $values) {
            $v = waRequest::post($field);
            if (!$v) {
                $this->response(array(
                    'error' => 'invalid_request',
                    'error_description' => 'Required parameter is missing: '.$field
                ), 400);
                return false;
            }
            if (is_array($values) && !in_array($v, $values)) {
                $this->response(array(
                    'error' => ($field == 'grant_type' ? 'unsupported_grant_type' : 'invalid_request'),
                    'error_description' => 'Invalid '.$field.': '.$v
                ), 400);
                return false;
            }
        }
        return true;
    }

    protected function corsWorkaround()
    {
        if ($origin = waRequest::server('HTTP_ORIGIN')) {
            wa()->getResponse()
                ->addHeader('Access-Control-Allow-Origin', $origin)
                ->addHeader('Access-Control-Allow-Credentials', 'true')
                ->addHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
                ->addHeader('Vary', 'Origin');
        }

        if (waRequest::server('REQUEST_METHOD') == 'OPTIONS') {
            wa()->getResponse()
                ->setStatus(200)
                ->sendHeaders();
            exit;
        }
    }

    protected function getAccessToken($code, $apps)
    {
        $url = $this->url_provider->getAuthCenterUrl('auth/token');
        $credentials = $this->client_manager->getCredentials();

        if (empty($credentials)) {
            return [
                'status_code' => 403,
                'error' => 'not_enabled',
                'error_description' => 'Webasyst ID service is not enabled'
            ];
        }

        $net_options = [
            'timeout' => 20,
            'format' => waNet::FORMAT_JSON,
            'request_format' => waNet::FORMAT_RAW,
            'expected_http_code' => null
        ];

        $net = new waNet($net_options);

        try {
            $params = [
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
                'code' => $code,
                'apps' => $apps,
                'grant_type' => 'authorization_code',
                'redirect_uri' => rtrim(wa()->getRootUrl(true), '/')    // exchange code to token demands redirect_uri verification
            ];

            $response = $net->query($url, $params, waNet::METHOD_POST);

            if (is_array($response)) {
                $error_code = isset($response['error_code']) ? $response['error_code'] : '';
                $error_description = isset($response['error_message']) ? $response['error_message'] : '';
                $status_code = $net->getResponseHeader('http_code');
                if ($error_code) {
                    return [
                        'status_code' => $status_code,
                        'error' => $error_code,
                        'error_description' => 'Webasyst ID service error: ' . $error_description
                    ];
                }
                $response['status_code'] = $status_code;
                return $response;
            }

        } catch (waException $e) {
            $this->logException($e);
        }

        return [
            'status_code' => 500,
            'error' => 'unexpected_response',
            'error_description' => 'Webasyst ID service returned unexpected response'
        ];
    }

    protected function dropKeys(array $array, array $keys)
    {
        $result = $array;
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        return $result;
    }

    protected function logException(Exception $e)
    {
        $message = join(PHP_EOL, [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);
        waLog::log($message, 'webasyst/' . get_class($this) . '.log');
    }
}
