<?php

/**
 * Base skeleton action for all actions in this module (wa-system/login)
 *
 * Class waLoginModuleController
 */
abstract class waLoginModuleController extends waViewAction
{
    protected $namespace;
    protected $response = array();
    private $is_json_mode;

    protected function assign($name, $value = null)
    {
        if (is_scalar($name)) {
            $this->response[$name] = $value;
        } elseif (is_array($name)) {
            $this->response = array_merge($this->response, $name);
        }
    }

    /**
     * @param array $options
     * @return waLoginFormRenderer|null
     */
    abstract protected function getFormRenderer($options = array());

    abstract protected function getLoginUrl();

    protected function afterExecute()
    {
        if (!$this->isJsonMode()) {

            wa()->getResponse()->setTitle($this->getTitle());

            $this->response = array_merge($this->response, array(
                'data'      => $this->getData(),
                'errors'    => $this->getErrors(),
                'messages'  => $this->getMessages(),
                'renderer'  => $this->getFormRenderer(),
                'login_url' => $this->getLoginUrl()
            ));

            // Backward compatibility
            if (!isset($this->response['error'])) {
                $this->response['error'] = $this->glueMessages($this->response['errors']);
            }

            $this->view->assign($this->response);
        }
    }

    /**
     * @return bool
     */
    protected function needRedirects()
    {
        $request = $this->getRequest()->request();
        // NOTICE: TRUE is default
        $need_redirects = true;
        if (array_key_exists('need_redirects', $request)) {
            $need_redirects = (bool)$request['need_redirects'];
        }
        return $need_redirects;
    }

    public function display($clear_assign = true)
    {
        if (!$this->isJsonMode()) {
            return parent::display($clear_assign);
        } else {

            $this->preExecute();
            $this->execute();
            $this->afterExecute();

            $errors = $this->getErrors();
            if ($errors) {
                $response = $this->response;
                if (isset($response['errors'])) {
                    unset($response['errors']);
                }
                $this->sendFailJsonResponse($errors, $response);
            } else {

                // We can't send waContact object by json, just array, so extract info from waContact
                if (isset($this->response['contact']) && $this->response['contact'] instanceof waContact) {
                    /**
                     * @var waContact $contact
                     */
                    $contact = $this->response['contact'];
                    $this->response['contact'] = array(
                        'id' => $contact->getId(),
                        'name' => waContactNameField::formatName($contact),
                        'firstname' => $contact['firstname'],
                        'lastname' => $contact['lastname'],
                        'middlename' => $contact['middlename'],
                        'userpic_20' => $contact->getPhoto(20)
                    );
                }

                $this->sendOkJsonResponse($this->response);
            }
        }
    }

    protected function isJsonMode()
    {
        if ($this->is_json_mode !== null) {
            return !!$this->is_json_mode;
        }
        $is_json_mode = $this->getRequest()->request('wa_json_mode');
        $is_ajax = waRequest::isXMLHttpRequest();
        $this->is_json_mode = $is_ajax && $is_json_mode;
        return $this->is_json_mode;
    }

    /**
     * @param array|waVerificationChannel[] $channels Array of channels
     * @param string $login
     * @return bool
     */
    protected function isChannelAvailable($channels, $login)
    {
        $is_available = false;

        if ($this->isValidEmail($login)) {
            $login_field = 'email';
        } elseif ($this->isValidPhoneNumber($login)) {
            $login_field = 'phone';
        } else {
            $login_field = 'login';
        }

        if ($login_field === 'login') {
            return true;
        }

        foreach ($channels as $channel) {
            $channel = waVerificationChannel::factory($channel);
            if ($login_field === 'email' && $channel->getType() === waVerificationChannelModel::TYPE_EMAIL) {
                $is_available = true;
                break;
            }
            if ($login_field === 'phone' && $channel->getType() === waVerificationChannelModel::TYPE_SMS) {
                $is_available = true;
                break;
            }
        }

        return $is_available;
    }

    protected function getChannelPriorityByLogin($login)
    {
        if ($this->isValidEmail($login)) {
            $priority = waVerificationChannelModel::TYPE_EMAIL;
        } elseif ($this->isValidPhoneNumber($login)) {
            $priority = waVerificationChannelModel::TYPE_SMS;
        } else {
            $priority = null;
        }
        return $priority;
    }

    /**
     * @param $string
     * @return bool
     */
    protected function isValidEmail($string)
    {
        if (!is_scalar($string)) {
            return false;
        }
        $validator = new waEmailValidator();
        return $validator->isValid((string)$string);
    }

    protected function isValidPhoneNumber($string)
    {
        if (!is_scalar($string)) {
            return false;
        }
        $validator = new waPhoneNumberValidator();
        return $validator->isValid((string)$string);
    }

    public function redirect($params = array(), $code = null)
    {
        $this->beforeRedirect($params, $code);
        if (!$this->isJsonMode()) {
            return parent::redirect($params, $code);
        } else {
            $url = $this->unpackRedirectParams($params);
            return $this->sendOkJsonResponse(array(
                'redirect_url' => $url,
                'redirect_code' => $code
            ));
        }
    }

    protected function beforeRedirect($params = array(), $code = null)
    {
        // override it
    }

    protected function sendFailJsonResponse($errors, $data = array())
    {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        $response = array('status' => 'fail', 'errors' => $errors, 'data' => $data);
        $this->getResponse()->sendHeaders();
        die(json_encode($response));
    }

    protected function sendOkJsonResponse($response)
    {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        $response = array('status' => 'ok', 'data' => $response);
        $this->getResponse()->sendHeaders();
        die(json_encode($response));
    }

    protected function getData($name = null)
    {
        if ($this->namespace !== null) {
            $post = $this->getRequest()->post($this->namespace);
        } else {
            $post = $this->getRequest()->post();
        }
        $post = $this->prepareData($post);
        if ($name !== null) {
            return isset($post[$name]) ? $post[$name] : null;
        } else {
            return $post;
        }
    }

    protected function prepareData($data)
    {
        return $data;
    }

    protected function getErrors()
    {
        $errors = isset($this->response['errors']) && is_array($this->response['errors']) ? $this->response['errors'] : array();
        return $this->formatMessagesArray($errors);
    }

    protected function getMessages()
    {
        $errors = isset($this->response['messages']) && is_array($this->response['messages']) ? $this->response['messages'] : array();
        return $this->formatMessagesArray($errors);
    }

    protected function formatMessagesArray($all_messages)
    {
        $messages = array();
        foreach ($all_messages as $group_name => $group_messages) {
            if (is_array($group_messages) || is_scalar($group_messages)) {
                $messages[$group_name] = (array)$group_messages;
            }
        }
        return $messages;
    }

    protected function glueMessages($all_messages, $glue = '<br>')
    {
        $messages = array();
        foreach ($all_messages as $group_messages) {
            foreach ($group_messages as $message) {
                $messages[] = $message;
            }
        }
        return join($glue, $messages);
    }

    protected function getScalarValue($key, array $data, $to_string = true)
    {
        $value = isset($data[$key]) && is_scalar($data[$key]) ? $data[$key] : '';
        return $to_string ? (string)$value : $value;
    }

    protected function logErrors($errors)
    {
        $errors = is_array($errors) || is_scalar($errors) ? (array)$errors : array();
        if (!$errors) {
            return;

        }
        $log_error = var_export(array(
            'errors' => $errors,
            'class_name' => get_class($this),
            'ip' => waRequest::getIp(),
            'user' => wa()->getUser() ? wa()->getUser()->getId() : null,
            'is_user_auth' => wa()->getUser() ? wa()->getUser()->isAuth() : null,
        ), true);
        waLog::log($log_error, 'login/action/error.log');
    }
}
