<?php

/**
 * Class waForgotPasswordAction
 *
 * Abstract action for restore password for frontend
 *
 * Must be called waFrontendForgotPasswordAction
 * But for backward compatibility with old Shop (and other apps) MUST be called waForgotPasswordAction
 *
 */
class waForgotPasswordAction extends waBaseForgotPasswordAction
{
    protected $env = 'frontend';
    protected $error_template = 'error.html';

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->auth_config = waDomainAuthConfig::factory();
    }

    /**
     * @param array $options
     * @return null
     */
    protected function getFormRenderer($options = array())
    {
        return null;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        try {
            parent::execute();
        } catch (Exception $e) {
            if ($this->error_template !== null) {
                $this->handleException($e);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Handle exception
     * This is default logic
     * Free to override it if in concrete app action handling exception presume other logic
     * @param Exception $e
     * @throws Exception
     */
    protected function handleException(Exception $e)
    {
        $ok = $this->setThemeTemplate($this->error_template);

        // if set template is not ok, probably because such file is not exist throw exception further
        if (!$ok) {
            throw $e;
        }

        $code = $e->getCode();
        if ($code > 600 || $code <= 400) {
            $code = 500;
        }

        $this->getResponse()->setStatus($code);
        $this->assign('error_code', $code);
        $this->assign('error_message', $e->getMessage());
    }
}
