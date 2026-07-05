<?php
class webasystDashboardAnnouncementsAIActions  extends waActions
{

    public function spellcheckAction()
    {
        if (!$this->isValid()) {
            return;
        }
        $text = waRequest::post('text');
        $result = $this->api()->serviceCall('AI', [
            'facility' => 'spellcheck',
            'content' => $text
        ], 'POST');

        return $this->handleResponse(ifset($result['response']));
    }

    public function writeAction()
    {
        if (!$this->isValid()) {
            return;
        }
        $text = waRequest::post('text');
        $emotion = waRequest::post('emotion');
        $locale = preg_match('~[а-я]~mui', $text) ? 'ru_RU' : 'en_US';

        $result = $this->api()->serviceCall('AI', [
            'facility' => 'announcement',
            'emotion' => $emotion, // fun|positive|neutral
            'objective' => $text,
            'locale' => $locale
        ], 'POST');

        if (!empty($result['response']['content'])) {
            $result['response']['content'] = $this->escapeScriptTags($result['response']['content']);
        }
        return $this->handleResponse(ifset($result['response']));
    }

    /**
     * @return installerServicesApi
     */
    private function api()
    {
        static $api;

        if (!$api) {
            $api = new waServicesApi();
        }

        return $api;
    }

    private function handleResponse($response)
    {
        if (!empty($response['content'])) {
            $this->displayJson($response['content']);
        } else if (!empty($response['error_description']) || !empty($response['error'])) {
            if (ifset($response, 'error', null) === 'payment_required') {
                $result = (new waServicesApi)->getBalanceCreditUrl('AI');
                if (ifset($result, 'response', 'url', false)) {
                    $response['error_description'] = str_replace('%s', 'href="'.$result['response']['url'].'"', $response['error_description']);
                }
            }
            $this->displayJson(null, $response);
        } else {
            throw new waException("Unable to contact AI API", 500);
        }
    }

    private function isValid() {
        $text = waRequest::post('text');
        if (!$text || !is_string($text)) {
            $this->displayJson(null, [
                'error' => 'incorrect_text',
                'error_description' => 'Incorrect text to process'
            ]);
            return false;
        }
        return true;
    }

    private function escapeScriptTags(string $str)
    {
        $result = preg_replace(
            '/<(\/?(script|style|link)(\s{1}[^>]*)*)>/i',
            '&lt;$1&gt;',
            $str
        );
        return $result;
    }
}
