<?php
class siteAiImageRequest
{
 const FIELDS_CACHE_TTL = 36000; // 10 hours

    public $facility = null;
    public $fields = null;
    public $sections = null;
    public $values = [];

    /**
     * @throws waException
     */
    public function loadFieldsFromApi(string $facility): siteAiImageRequest
    {
        $this->facility = $facility;
        $cache = new waVarExportCache('ai_fields_'.$facility.'_'.wa()->getLocale(), self::FIELDS_CACHE_TTL, 'site');
        $api_call = $cache->get();
        if (!$api_call) {
            $api = new waServicesApi();
            if (!$api->isConnected()) {
                return $this;
            }
            $api_call = $api->serviceCall('AI_OVERVIEW', [
                'locale' => wa()->getLocale(),
                'facility' => $facility,
            ]);
            if (empty($api_call['response']['fields']) || empty($api_call['response']['sections'])) {
                throw new waException('Unexpected response from WAID API');
            }
            $cache->set($api_call);
        }

        $this->sections = $api_call['response']['sections'];
        $this->fields = [];
        foreach($api_call['response']['fields'] as $f) {
            $this->fields[$f['id']] = $f;
        }

        return $this;
    }

    public function generate($options=[])
    {
        if (!$this->facility) {
            throw new waException('loadFieldsFromApi() must be called before generate()');
        }
        $api = new waServicesApi();
        if (!$api->isConnected()) {
            throw new waException('WAID is not connected');
        }

        $request_data = $this->values;
        $request_data['facility'] = $this->facility;
        if (!isset($request_data['locale'])) {
            // locale may be used for error messages
            $request_data['locale'] = wa()->getLocale();
        }

        $api_call = $api->serviceCall('AI', $request_data, 'POST', $options);
        if (empty($api_call['response'])) {
            return [
                'error' => 'unable_to_connect',
                'error_description' => _w('Service temporarily unavailable. Please try again later.'),
            ];
        }

        return $api_call['response'];
    }

    public function getFieldsWithValues(): array
    {
        if (!$this->fields) {
            return [];
        }

        $all_fields = [];
        foreach ($this->fields as $f) {
            $f['value'] = ifset($this->values, $f['id'], '');
            $all_fields[$f['id']] = $f;
        }
        return $all_fields;
    }

    public function getSectionsWithFields(): array
    {
        if (!$this->sections) {
            return [];
        }

        $all_fields = $this->getFieldsWithValues();

        $result = [];
        foreach ($this->sections as $s) {
            $section = [
                'title' => ifset($s, 'title', ''),
                'fields' => [],
            ];
            foreach ($s['fields'] as $f_id) {
                if (isset($all_fields[$f_id])) {
                    $section['fields'][] = $all_fields[$f_id];
                }
            }
            if ($section['fields']) {
                $result[] = $section;
            }
        }
        return $result;
    }

    public function setFieldValues(array $values): siteAiImageRequest
    {
        foreach ($values as $k => $v) {
            $this->setFieldValue($k, $v);
        }
        return $this;
    }

    public function setFieldValue($field_id, $value): siteAiImageRequest
    {
        $this->values[$field_id] = $value;
        return $this;
    }

    public function setPrompt($prompt = ''): siteAiImageRequest
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new waException(_w('Describe the image'), 400);
        }
        $this->setFieldValue('image_prompt', $prompt);

        return $this;
    }
}
