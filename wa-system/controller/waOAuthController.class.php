<?php

/**
 * Class waOAuthController
 * Controller for oauth.php?provider=<provider_id>
 */
class waOAuthController extends waViewController
{
    public function execute()
    {
        $provider_id = $this->getAuthProviderId();

        $is_webasyst_id_auth = $provider_id === waWebasystIDAuthAdapter::PROVIDER_ID;

        // Webasyst ID auth provider case not supports app related OAuth controller
        $ignore_app_controller = $is_webasyst_id_auth;

        if (!$ignore_app_controller) {
            // Remember in session who is handling the auth.
            // Important for multi-step auth such as OAuth2.
            $app = waRequest::get('app', null, 'string');
            if ($app) {
                $this->getStorage()->set('auth_app', $app);
                $params = waRequest::get();
                unset($params['app'], $params['provider']);
                if ($params) {
                    $this->getStorage()->set('auth_params', $params);
                }
            }

            // Make sure the correct app is handling the request
            $app = $this->getStorage()->get('auth_app');
            if ($app && $app != wa()->getApp()) {
                if (wa()->appExists($app)) {
                    return wa($app, true)->getFrontController()->execute(null, 'OAuth');
                } else {
                    $this->cleanup();
                    throw new waException("Page not found", 404);
                }
            }
        }


        try {
            // Look into wa-config/auth.php, find provider settings
            // and instantiate proper class.

            $provider_id = $this->getAuthProviderId();
            if (!$provider_id) {
                throw new waException('Unknown adapter ID');
            }

            $type = $this->getAuthType();
            if ($provider_id === waWebasystIDAuthAdapter::PROVIDER_ID && (!$type || $type === waWebasystIDAuthAdapter::TYPE_WA)) {
                $auth = new waWebasystIDWAAuth();
            } else {
                $auth = $this->getAuthAdapter($provider_id);
            }

            // Webasyst ID WA Auth (auth in WA backend by webasyst ID)
            if ($auth instanceof waWebasystIDWAAuth) {
                $controller = new waWebasystIDWAAuthController($auth);
                $controller->execute();
                return;
            }


            // Use waAuthAdapter to identify the user.
            // In case of waOAuth2Adapter, things are rather complicated:
            // 1) When user has just opened the oauth popup, adapter redirects them
            //    to external URL and exits.
            // 2) External resource does its magic and then redirects back here,
            //    with a code in GET parameters.
            // 3) That second time, adapter uses the code from GET to fetch user data
            //    from external resource and return here if all goes well.
            $auth_response_data = $auth->auth();
            if (!$auth_response_data) {
                throw new waException('Unable to finish auth process.');
            }

            // Person identified. Now properly authorise them as local waContact,
            // possibly creating new waContact from data provided.
            $result = $this->afterAuth($auth_response_data);

            $this->cleanup();

            $this->displayAuth($result);
        } catch (waWebasystIDAccessDeniedAuthException $e) {
            $this->cleanup();
            // if webasyst ID server response 'access_denied' it means that user not allowed authorization, so not showing error (just finish proccess)
            $this->displayAuth([]);
        } catch (waWebasystIDAuthException $e) {
            $this->cleanup();
            $this->displayError($e->getMessage());  // show legitimate error from webasyst ID auth adapter
        } catch (Exception $e) {
            $this->cleanup();
            throw $e; // Caught in waSystem->dispatch()
        }
    }

    protected function getAuthProviderId()
    {
        // callback url might looks like this: oauth.php?provider=<provider_id>
        $provider_id = waRequest::get('provider', '', waRequest::TYPE_STRING_TRIM);
        if ($provider_id) {
            return $provider_id;
        }

        // or callback url might looks like this: oauth.php/<auth_adapter_id>/
        $request_url = wa()->getConfig()->getRequestUrl(true, true);

        $tail_part = trim(substr($request_url, 9), '/');
        if ($tail_part) {
            $parts = explode('/', $tail_part);
            if (!empty($parts[0])) {
                return $parts[0];
            }
        }

        return null;
    }

    /**
     * Supported only by webasyst ID provider
     * @return string
     */
    protected function getAuthType()
    {
        return waRequest::get('type', '', waRequest::TYPE_STRING_TRIM);
    }

    /**
     * @param string $provider
     * @return object|waAuthAdapter|waiAuth
     * @throws waException
     */
    protected function getAuthAdapter($provider)
    {
        $config = wa()->getAuthConfig();
        if (!isset($config['adapters'][$provider])) {
            throw new waException('Unknown auth provider');
        }

        return wa()->getAuth($provider, $config['adapters'][$provider]);
    }

    /**
     * @param $result
     * @throws waException
     */
    protected function displayAuth($result)
    {
        wa('webasyst');

        $provider_id = $this->getAuthProviderId();

        $params = [
            'provider_id' => $provider_id,
            'result' => $result
        ];
        
        $this->executeAction(new webasystOAuthAction($params));
    }

    protected function displayError($error)
    {
        echo $error;
        exit;
    }

    /**
     * @param array $data
     * @return waContact
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waException
     */
    protected function afterAuth($data)
    {
        $contact_id = 0;
        // find contact by auth adapter id, i.e. facebook_id
        $contact_data_model = new waContactDataModel();
        $row = $contact_data_model->getByField(array(
            'field' => $data['source'].'_id',
            'value' => $data['source_id'],
            'sort' => 0
        ));
        if ($row) {
            $contact_id = $row['contact_id'];
        }

        if (wa()->getUser()->isAuth()) {
            $contact = wa()->getUser();
            if ($contact_id && $contact_id != $contact->getId()) {
                // delete old link
                $contact_data_model->deleteByField(array(
                    'contact_id' => $contact_id,
                    'field' => $data['source'].'_id'
                ));
            }
            // save the link
            $contact->save(array(
                $data['source'].'_id' => $data['source_id']
            ));
            $contact_id = $contact->getId();
        }

        // try find user by email
        if (!$contact_id && isset($data['email'])) {

            $email = $data['email'];
            if (is_array($data['email']) && isset($data['email'][0]['value'])) {
                $email = $data['email'][0]['value'];
            }

            $contact_model = new waContactModel();
            $sql = "SELECT c.id FROM wa_contact_emails e
                        JOIN wa_contact c ON e.contact_id = c.id
                    WHERE e.email LIKE '".$contact_model->escape($email, 'like')."' AND e.sort = 0 AND c.password != ''";

            $contact_id = $contact_model->query($sql)->fetchField('id');

            // save source_id
            if ($contact_id) {
                $tmp = array(
                    'contact_id' => $contact_id,
                    'field' => $data['source'].'_id',
                    'sort' => 0
                );
                // contact already has this source
                $row = $contact_data_model->getByField($tmp);
                if ($row) {
                    $contact_data_model->updateByField($tmp, array('value' => $data['source_id']));
                } else {
                    $tmp['value'] = $data['source_id'];
                    $contact_data_model->insert($tmp);
                }
            }
        }

        // create new contact
        if (!$contact_id) {
            $contact = $this->createContact($data);
            if ($contact) {
                $contact_id = $contact->getId();
            }
        } elseif (empty($contact)) {
            $contact = new waContact($contact_id);
        }

        // auth user
        if ($contact_id) {
            if (!wa()->getUser()->isAuth()) {
                wa()->getAuth()->auth(array('id' => $contact_id));
            }
            return $contact;
        }

        return false;
    }

    /**
     * @param array $data
     * @return waContact
     * @throws waException
     */
    protected function createContact($data)
    {
        $app_id = $this->getStorage()->get('auth_app');

        $contact = new waContact();
        $data[$data['source'].'_id'] = $data['source_id'];
        $data['create_method'] = $data['source'];
        $data['create_app_id'] = $app_id;
        // set random password (length = default hash length - 1, to disable ability auth using login and password)
        $contact->setPassword(substr(waContact::getPasswordHash(uniqid(time(), true)), 0, -1), true);
        unset($data['source']);
        unset($data['source_id']);
        if (isset($data['photo_url'])) {
            $photo_url = $data['photo_url'];
            unset($data['photo_url']);
        } else {
            $photo_url = false;
        }
        $errors = $contact->save($data);
        if ($errors) {
            $error = '';
            foreach ($errors as $field => $field_errors) {
                $f = waContactFields::get($field);
                if ($f) {
                    $error = '<b>'.$f->getName().'</b>: '.implode(' ', $field_errors);
                }
            }
            $this->displayError($error);
        }
        $contact_id = $contact->getId();

        if ($contact_id && $photo_url) {
            // Load person photo and save to contact
            $photo = null;
            if (function_exists('curl_init')) {
                $ch = curl_init($photo_url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
                $photo = curl_exec($ch);
                curl_close($ch);
            } else {
                $scheme = parse_url($photo_url, PHP_URL_SCHEME);
                if (ini_get('allow_url_fopen') && in_array($scheme, stream_get_wrappers())) {
                    $photo = @file_get_contents($photo_url);
                }
            }
            if ($photo) {
                $photo_url_parts = explode('/', $photo_url);
                $path = wa()->getTempPath('auth_photo/'.$contact_id.'.'.md5(end($photo_url_parts)), $app_id);
                file_put_contents($path, $photo);

                try {
                    $contact->setPhoto($path);
                } catch (Exception $exception) {

                }
            }
        }

        /**
         * @event signup
         * @param waContact $contact
         */
        wa()->event('signup', $contact);

        $this->logAction('signup', wa()->getEnv(), null, $contact->getId());

        return $contact;
    }

    protected function cleanup()
    {
        $this->getStorage()->del('auth_params');
        $this->getStorage()->del('auth_app');
    }
}
