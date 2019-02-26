<?php

/**
 * Class waFrontendLoginForm
 *
 * Concrete class for rendering login form in frontend environment
 *
 */
class waFrontendLoginForm extends waLoginForm
{
    /**
     * @var string
     */
    protected $env = 'frontend';

    /**
     * @var null|string
     */
    protected $url;

    /**
     * waFrontendLoginForm constructor.
     * @param array $options - options are inherited
     *   Additional options
     *     string|null 'url' - custom url of login action
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->auth_config = waDomainAuthConfig::factory();
        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/login/frontend/';

        // Who has generate password and sent it to client?
        $action_generated_password = $this->getActionGeneratedPassword();

        if ($action_generated_password) {

            // No need show adapters in this case
            $this->options['show_oauth_adapters'] = false;

            if (!isset($this->options['title'])) {
                if ($action_generated_password === 'forgotpassword') {
                    // It was 'forgotpassword' action - means we were passing recovery password process
                    $this->options['title'] = _ws('Password recovery');
                } elseif ($action_generated_password === 'signup') {
                    // It was 'signup' action - means we were passing signin up process
                    $this->options['title'] = _ws('Signup completed');
                } else {
                    // Just in case
                    $this->options['title'] = _ws('Passwords changed');
                }
            }

            if (!isset($this->options['sub_title'])) {
                $this->options['sub_title'] = _ws('Log in');
            }

        } else {
            if (!isset($this->options['title'])) {
                $this->options['title'] = _ws('Log in');
            }
        }

        if (isset($this->options['url'])) {
            $this->url = $this->options['url'];
        } else {
            $this->url = $this->auth_config->getLoginUrl();
        }
    }


    /**
     * Render 'remember me' control
     * Takes into account proper auth config option
     * @return string
     */
    public function renderRememberMe()
    {
        if (!$this->auth_config->getRememberMe()) {
            return '';
        }
        return parent::renderRememberMe();
    }

    /**
     * Render messages
     * @return array
     */
    public function getMessages()
    {
        $messages = $this->messages;
        $msg = $this->getGeneratedPasswordSentMessage();
        if ($msg) {
            $messages['generated_password_sent'] = array($msg);
        }
        return $messages;
    }

    /**
     * Login url
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->url;
    }

    /**
     * Get value for field
     * @param string $field_id
     * @return mixed
     */
    protected function getContactFieldValue($field_id)
    {
        if (isset($this->data[$field_id])) {
            return $this->data[$field_id];
        } elseif ($field_id === 'login' && ($address = $this->getUsedAddress())) {
            return $address;
        } else {
            return null;
        }
    }

    /**
     * Get info from last response of forgot-password action
     *
     * NOTICE: delete response from storage right away, cause we need process this response only 1 time!
     *
     */
    protected function getForgotPasswordLastResponse()
    {
        static $response;
        if (!$response) {
            $key = 'wa/forgotpassword/last_response';
            $response = wa()->getStorage()->get($key);
            $response = is_array($response) ? $response : array();
            wa()->getStorage()->del($key);
        }
        return $response;
    }


    /**
     * Get info from last response of signing up
     *
     * NOTICE: delete response from storage right away, cause we need process this response only 1 time!
     *
     * @return array
     */
    protected function getSignupLastResponse()
    {
        static $response;
        if (!$response) {
            $response = wa()->getStorage()->get('wa/signup/last_response');
            $response = is_array($response) ? $response : array();
            wa()->getStorage()->del('wa/signup/last_response');
        }
        return $response;
    }

    /**
     * @return array
     */
    protected function getLastResponses()
    {
        // Get last responses to lookup through they
        // IMPORTANT: Order matter
        return array(
            'forgotpassword' => $this->getForgotPasswordLastResponse(),
            'signup' => $this->getSignupLastResponse()
        );
    }


    /**
     * If NOT NULL than password recovery OR signing up just happened and generated password sent
     *
     * @see getLastResponses
     *
     * @return string|null
     *   Variants of strings 'forgotpassword', 'signup'
     *
     */
    protected function getActionGeneratedPassword()
    {
        foreach ($this->getLastResponses() as $response_action => $last_response) {
            if (!empty($last_response) && !empty($last_response['generated_password_sent'])) {
                return $response_action;
            }
        }
        return null;
    }

    /**
     * Get address where generated password just sent (if was so)
     * @see getActionGeneratedPassword
     * @return string
     */
    protected function getUsedAddress()
    {
        foreach ($this->getLastResponses() as $last_response) {
            if ($last_response) {
                $address = isset($last_response['used_address']) && is_scalar($last_response['used_address']) ? (string)$last_response['used_address'] : '';
                return $address;
            }
        }
        return '';
    }

    /**
     * Get message to show user when generated password has been sent
     * @return string
     */
    protected function getGeneratedPasswordSentMessage()
    {
        foreach ($this->getLastResponses() as $last_response) {
            if ($last_response) {
                $msg = '';
                if (isset($last_response['generated_password_sent_message']) && is_scalar($last_response['generated_password_sent_message'])) {
                    $msg = (string)$last_response['generated_password_sent_message'];
                }
                return $msg;
            }
        }
        return '';
    }
}
