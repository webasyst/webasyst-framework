<?php

class waReCaptcha extends waAbstractCaptcha
{
    // required options
    protected  $required = array(
        'sitekey',
        'secret'
    );

    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function getHtml()
    {
        $sitekey = json_encode($this->options['sitekey']);

        // Explicit rendering, as well as $.getScript(), are used
        // to allow multiple ReCaptchas on the same page.

        // Click to .wa-captcha-refresh is used in templates to programmatically reset WA CAPTCHAs.
        // An invisible element is added here to support the same behaviour.

        return <<<HTML
<script>(function() {
    if (window.onloadWaRecaptchaCallback) {
        return;
    }

    window.onloadWaRecaptchaCallback = function() {
        var sitekey = {$sitekey};
        if (!window.grecaptcha) return;
        $('.g-recaptcha:not(.initialized)').each(function() {
            var wrapper = $(this).addClass('initialized');
            var widget_id = grecaptcha.render(wrapper[0], { sitekey: sitekey });
            wrapper.siblings('.wa-captcha-refresh').click(function() {
                try {
                    grecaptcha.reset(widget_id);
                } catch (e) {
                    console.log('Unable to reset WA ReCaptcha widget id =', widget_id);
                    console.log(e);
                }
                return false;
            });
        });
    };

    $(function() {
        if (window.grecaptcha) {
            window.onloadWaRecaptchaCallback();
        } else {
            $.getScript("https://www.google.com/recaptcha/api.js?render=explicit&onload=onloadWaRecaptchaCallback");
        }
    });
})();</script>
<div class="wa-captcha wa-recaptcha">
    <a class="wa-captcha-refresh" style="display:none;"></a>
    <div class="g-recaptcha"></div>
</div>
HTML;
    }

    public function isValid($code = null, &$error = '')
    {
        if ($code === null) {
            $code = waRequest::post('g-recaptcha-response');
        }
        $handle = curl_init(self::SITE_VERIFY_URL);
        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array(
                'secret' => $this->options['secret'],
                'response' => $code,
                'remoteip' => waRequest::getIp(),
            )),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
            CURLINFO_HEADER_OUT => false,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true
        );
        curl_setopt_array($handle, $options);
        $response = curl_exec($handle);
        curl_close($handle);
        if ($response) {
            $response = json_decode($response, true);
            if (isset($response['success']) && $response['success'] == true) {
                return true;
            } elseif (isset($response['error-codes'])) {
                $errors = array();
                foreach ($response['error-codes'] as $error_code) {
                    switch ($error_code) {
                        case 'missing-input-secret':
                            $errors[] = _ws('The secret parameter is missing.');
                        break;
                        case 'invalid-input-secret':
                            $errors[] = _ws('The secret parameter is invalid or malformed.');
                            break;
                        case 'missing-input-response':
                            $errors[] = _ws('The response parameter is missing.');
                            break;
                        case 'invalid-input-response':
                            $errors[] = _ws('The response parameter is invalid or malformed.');
                            break;
                        default:
                            $errors[] = $error_code;
                    }
                    $error = implode('<br>', $errors);
                }
            }
        }
        return false;
    }

    public function display()
    {

    }
}