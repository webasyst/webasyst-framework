<?php
/**
 * Generate settings fields for AI requests.
 */
class siteBlocksAiSettingsController extends waJsonController
{
    const FIELDS_CACHE_TTL = 36000; // 10 hours

    public function execute()
    {
        $facility = waRequest::request('facility', '', 'string');
        //$objective = waRequest::request('objective', '', 'string');
        $locale = waRequest::request('locale', 'ru_RU', 'string');
        try { 
        $result['facility'] = $facility;
        $cache = new waVarExportCache('ai_fields_'.$facility.'_'.wa()->getLocale(), self::FIELDS_CACHE_TTL, 'site');
        $api_call = $cache->get();
        if (!$api_call) {
            $api = new waServicesApi();
            if (!$api->isConnected()) {
                throw new waException('WAID is not connected');
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
        $result['sections'] = $api_call['response']['sections'];
        $result['fields'] = [];
        foreach($api_call['response']['fields'] as $f) {
            $result['fields'][$f['id']] = $f;
        }   
        $this->response = $result;  
        } catch (Exception $e) {
            $this->errors = [
                'error' => $e->getMessage(),
                'error_description' => "Error message: '{$e->getMessage()}',\n Error code: '{$e->getCode()}'"
            ];
        }
    }

/*
    public function getSections()
    {
        $request = (new shopAiApiRequest())
                ->loadFieldsFromApi('store_product')
                ->loadFieldValuesFromSettings();

        unset(
            $request->fields['text_length'],
            $request->fields['product_name'],
            $request->fields['categories'],
            $request->fields['advantages'],
            $request->fields['traits']
        );

        return $request->getSectionsWithFields();
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
                'title' => $s['title'],
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
*/
}
