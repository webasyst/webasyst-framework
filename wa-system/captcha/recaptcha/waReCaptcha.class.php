<?php

class waReCaptcha extends waAbstractCaptcha
{
    // required options
    protected  $required = array(
        'sitekey',
        'secret'
    );

    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    // for invisible captcha only - save success result in session
    // if user pass captcha test once in this session consider user is not a robot
    // need to prevent problem where there is multiple captcha instances in page
    const SESSION_KEY = 'recaptcha_passed';

    public function getHtml()
    {
        $wrapper_class = ifempty($this->options, 'wrapper_class', 'wa-captcha');
        $sitekey = ifset($this->options['sitekey']);
        $invisible = ifset($this->options['invisible']);

        $view = wa('webasyst')->getView();
        $view->assign(array(
            'wrapper_class'    => $wrapper_class,
            'sitekey'          => $sitekey,
            'recaptcha_passed' => $invisible && wa()->getStorage()->get(self::SESSION_KEY),
        ));

        $template = wa()->getConfig()->getRootPath() .'/wa-system/captcha/recaptcha/templates/recaptcha.html';
        if ($invisible) {
            $template = wa()->getConfig()->getRootPath() .'/wa-system/captcha/recaptcha/templates/recaptcha_invisible.html';
        }
        return $view->fetch($template);
    }

    public function isValid($code = null, &$error = '')
    {
        if ($code) {
            return $this->verify($code, $error);
        }

        $code = waRequest::cookie('g-recaptcha-response');
        $invisible = ifset($this->options['invisible']);
        if ($invisible && wa()->getStorage()->get(self::SESSION_KEY)) {
            return true;
        }

        $is_valid = $this->verify($code, $error);
        if ($invisible && $is_valid) {
            wa()->getStorage()->set(self::SESSION_KEY, true);
        }

        return $is_valid;
    }

    protected function verify($code = null, &$error = '')
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
