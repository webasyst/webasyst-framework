<?php 

include dirname(__FILE__).'/recaptchalib.php';

class waReCaptcha extends waAbstractCaptcha
{
    // required options
    protected  $required = array(
        'publickey',
        'privatekey'
    );
    
    public function getHtml()
    {
        return recaptcha_get_html($this->options['publickey']);
    }
    
    public function isValid($code = null)
    {
        $response = recaptcha_check_answer($this->options['privatekey'],
                                waRequest::server("REMOTE_ADDR"),
                                waRequest::post("recaptcha_challenge_field"),
                                waRequest::post("recaptcha_response_field"));
        return $response->is_valid;
    }

    public function display()
    {

    }
}