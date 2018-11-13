<?php

class waVerificationChannelNull extends waVerificationChannel
{
    public function __construct() {}

    public function exists()
    {
        return false;
    }

    protected function loadInfo($id)
    {
        return self::getModel()->getEmptyRow();
    }

    protected function loadParams($id)
    {
        return array();
    }

    public function save($data, $delete_old_params = false) {}

    /**
     * @param string|array|waContact|id $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options Depends on concrete implementation of method
     * @return mixed
     */
    public function sendSignUpConfirmationMessage($recipient, $options = array())
    {
        return false;
    }

    /**
     * @param string $confirmation_secret
     * @param array $options
     * @return bool
     */
    public function validateSignUpConfirmation($confirmation_secret, $options = array())
    {
        return false;
    }

    /**
     * @param string|array|waContact|id $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options Depends on concrete implementation of method
     * @return mixed
     */
    public function sendSignUpSuccessNotification($recipient, $options = array())
    {
        return false;
    }

    /**
     * @param string|array|waContact|id $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options Depends on concrete implementation of method
     * @return mixed
     */
    public function sendOnetimePasswordMessage($recipient, $options = array())
    {
        return false;
    }

    /**
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     * @return null|array
     *   + 'address' Required
     *   + other optional fields
     */
    protected function typecastInputRecipient($recipient)
    {
        return null;
    }

    /**
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options Depends on concrete implementation of method
     * @return mixed
     */
    public function sendRecoveryPasswordMessage($recipient, $options = array())
    {
        return false;
    }

    /**
     * @param string $secret
     * @param array $options
     * @return bool
     */
    public function validateRecoveryPasswordSecret($secret, $options = array())
    {
        return false;
    }

    public function invalidateRecoveryPasswordSecret($secret, $options = array())
    {
        return;
    }

    public function sendPassword($recipient, $password, $options = array())
    {
        return false;
    }

    public function validateOnetimePassword($password, $options = array())
    {
        return false;
    }
}
