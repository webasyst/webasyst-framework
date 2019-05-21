<?php

/**
 * Class waVerificationChannelNull
 *
 * Null verification channel
 * Doesn't do any work
 * Enhances code stability and reduces redundant IF checking in application code
 *
 * @see https://en.wikipedia.org/wiki/Null_object_pattern
 *
 */
class waVerificationChannelNull extends waVerificationChannel
{
    /**
     * waVerificationChannelNull constructor.
     */
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
     *
     * Has been sent confirmation message for signup
     *
     * @param string|array|waContact $recipient recipient to send confirmation
     *  - string: means 'address' where send confirmation message
     *  - array: have keys
     *    + 'address' ('email','phone') field where send confirmation message
     *    + 'name' Optional. Name of recipient
     *  - waContact: extract from object proper info for send confirmation message
     *  - id: means contact ID, extract by this ID proper info for send confirmation message
     *
     * @param array $options For feature use
     * @return bool
     */
    public function hasSentSignUpConfirmationMessage($recipient, $options = array())
    {
        return false;
    }

    /**
     * @param string $confirmation_secret
     * @param array $options
     * @return array Associative array
     *
     *   Format of this associative array:
     *
     *   - bool  'status'  - successful or not was validation
     *
     *   - array 'details' - detailed information about result of validation
     *      Format of details depends on 'status'
     *        If 'status' is TRUE
     *          - string 'address'     - address that was validated
     *          - int    'contact_id'  - id of contact bind to this address
     *        Otherwise details is empty array
     */
    public function validateSignUpConfirmation($confirmation_secret, $options = array())
    {
        return array(
            'status' => false,
            'details' => array(
                'error' => self::VERIFY_ERROR_INVALID
            )
        );
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

    protected function validateSecret($secret, $asset_name, $options = array())
    {
        return array(
            'status'  => false,
            'details' => array(
                'tries'      => null,
                'rest_tries' => null,
                'error'      => self::VERIFY_ERROR_INVALID
            )
        );
    }

    public function validateRecoveryPasswordSecret($secret, $options = array())
    {
        return $this->validateSecret($secret, $options);
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

    public function sendConfirmationCodeMessage($recipient, $options = array())
    {
        return false;
    }

    public function isWorking()
    {
        return false;
    }

    /**
     * Get vars name for each predefined template, optionally with description
     * @param string $template_name
     * @param bool $with_description
     * @return array
     */
    public function getTemplateVars($template_name, $with_description = false)
    {
        return array();
    }

    /**
     * Compare 2 secret for equal
     * @param $input_secret
     * @param $asset_secret
     * @param $asset_name
     * @return bool
     */
    protected function isSecretEquals($input_secret, $asset_secret, $asset_name)
    {
        return false;
    }
}
