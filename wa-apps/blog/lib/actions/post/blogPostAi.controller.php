<?php
class blogPostAiController extends waJsonController
{
    public function execute()
    {
        switch (waRequest::post('action')) {
            case 'spellcheck':
                $content = $this->spellcheckAction();
                break;
            case 'blogPost':
                $content = $this->blogPostAction();
                break;
            case 'tryFree':
                $this->tryFreeAction();
                break;
            default:
                throw new waException(_w('An error has occurred. Please reload this page and try again.'));
        }

        if (isset($content)) {
            $this->response['content'] = $content;
        }
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

    private function spellcheckAction()
    {
        $this->checkLicensing();
        if (($text = $this->getValidText()) === false) {
            return;
        };
        $result = $this->api()->serviceCall('AI', [
            'facility' => 'spellcheck',
            'locale' => wa()->getUser()->getLocale(),
            'content' => $text
        ], 'POST');

        return $this->handleResponse(ifset($result['response']));
    }

    private function blogPostAction()
    {
        $this->checkLicensing();
        if (($text = $this->getValidText()) === false) {
            return;
        };
        $locale = preg_match('~[а-я]~mui', $text) ? 'ru_RU' : 'en_US';

        $result = $this->api()->serviceCall('AI', [
            'facility' => 'blog_post',
            'text_length' => 'medium',
            'text_style' => 'positive',
            'objective' => $text,
            'locale' => $locale
        ], 'POST');

        if (!empty($result['response']['content'])) {
            $result['response']['content'] = $this->escapeScriptTags($result['response']['content']);
        }

        return $this->handleResponse(ifset($result['response']));
    }

    private function tryFreeAction() {
        $app_settings_model = new waAppSettingsModel();
        $prev_count = $app_settings_model->get('blog', 'blog_try_free_count', 0) + 1;
        $app_settings_model->set('blog', 'blog_try_free_count', $prev_count);
        $this->response['is_max_count'] = $prev_count >= 10;
    }

    private function getValidText()
    {
        $text = waRequest::post('text');
        if (!$text || !is_string($text)) {
            $this->errors = [
                'error' => 'incorrect_text',
                'error_description' => 'Incorrect text to process'
            ];
            return false;
        }
        return $text;
    }

    private function handleResponse($response)
    {
        if (!empty($response['content'])) {
            return $response['content'];
        } else if (!empty($response['error_description']) || !empty($response['error'])) {
            if (ifset($response, 'error', null) === 'payment_required') {
                $result = (new waServicesApi)->getBalanceCreditUrl('AI');
                if (ifset($result, 'response', 'url', false)) {
                    $response['error_description'] = str_replace('%s', 'href="'.$result['response']['url'].'"', $response['error_description']);
                }
            }
            $this->errors = $response;
        } else {
            throw new waException("Unable to contact AI API", 500);
        }

        return null;
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

    protected function checkLicensing()
    {
        // has premium?
        if (waLicensing::check('blog')->hasPremiumLicense()) {
            return true;
        }
        // free limit available?
        $app_settings_model = new waAppSettingsModel();
        $prev_count = $app_settings_model->get('blog', 'blog_try_free_count', 0);
        if ($prev_count <= 10) {
            return true;
        }
        throw new waRightsException("Premium license is required");
    }
}
