<?php
/**
 * Generate product description using AI.
 */
class siteBlocksAiGenerateController extends waJsonController
{
    public $values = [];

    public function execute()
    {
        //$facility = waRequest::request('facility', '', 'string');
        //$objective = waRequest::request('objective', '', 'string');
        //$locale = waRequest::request('locale', 'ru_RU', 'string');
        $request_data = waRequest::post();

        try {
            /*if (!$facility) {
                throw new waException('loadFieldsFromApi() must be called before generate()');
            }*/
            $api = new waServicesApi();
            if (!$api->isConnected()) {
                throw new waException('WAID is not connected');
            }

            $api_call = $api->serviceCall('AI', $request_data, 'POST', [
                'timeout' => 30,
            ]);

            if (empty($api_call['response'])) {
                $this->errors = [
                    'error' => 'unable_to_connect',
                    'error_description' => _w('Service temporarily unavailable. Please try again later.'),
                ];
            }

            if ($api_call['status'] !== 200) {
                $this->errors = $api_call['response'];
                return;
            }

            $this->response = $api_call;

        } catch (Exception $e) {
            //$this->response = "Error message: '{$e->getMessage()}'\n Error code: '{$e->getCode()}'";
            $this->errors = [
                'error' => $e->getMessage(),
                'error_description' => "Error message: '{$e->getMessage()}',\n Error code: '{$e->getCode()}'"
            ];
        }
    }
}
