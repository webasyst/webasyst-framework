<?php

class waOAuthController extends waViewController
{
    public function execute()
    {
        $provider_id = $this->getAuthProviderId();

        $is_webasyst_id_auth = $provider_id === waWebasystIDAuth::PROVIDER_ID;

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

            $auth = $this->getAuthAdapter($provider_id);

            // this is about webasyst ID binding, see php doc of methods
            if ($is_webasyst_id_auth && $this->isUnfinishedBindingProcess()) {
                $this->finishBindingProcess($auth);
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

            if ($is_webasyst_id_auth) {
                /**
                 * @var waWebasystIDAuth $auth
                 */
                if ($auth->isBackendAuth()) {

                    $result = $this->authBackendUser($auth_response_data);

                    // save result in session
                    $this->getStorage()->set('webasyst_id_backend_auth_result', $result);

                    // if auth fail because of not bounding, save server data in session for latter use
                    if (!$result['status'] && $result['details']['error_code'] === 'not_bound') {
                        $this->getStorage()->set('webasyst_id_server_data', $auth_response_data);
                    }
                } else {

                    $result = $auth->bindWithWebasystContact($auth_response_data);

                    // if binding fail, save server data in session for latter use
                    if (!$result['status']) {
                        $this->getStorage()->set('webasyst_id_server_data', $auth_response_data);
                    }

                    // wrap so we can differ this response from others
                    $result = ['type' => 'bind_with_webasyst_contact', 'result' => $result];
                }
            } else {
                // Person identified. Now properly authorise them as local waContact,
                // possibly creating new waContact from data provided.
                $result = $this->afterAuth($auth_response_data);
            }

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
        $provider_id = waRequest::get('provider', '', 'string');
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

    protected function getAuthAdapter($provider)
    {
        if ($provider === waWebasystIDAuth::PROVIDER_ID) {
            return new waWebasystIDAuth();
        }

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
     * @throws waDbException
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
            $contact_model = new waContactModel();
            $sql = "SELECT c.id FROM wa_contact_emails e
            JOIN wa_contact c ON e.contact_id = c.id
            WHERE e.email LIKE '".$contact_model->escape($data['email'], 'like')."' AND e.sort = 0 AND c.password != ''";
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
     * Authorize backend user by params that we get from Webasyst ID service
     *
     * @param array $params - here is access token params with expected format:
     *      - string $params['access_token'] [required] - access token itself (jwt)
     *      - string $params['refresh_token'] [required] - refresh token to refresh access token
     *      - int    $params['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $params['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     * @return array $result
     *      - bool  $result['status'] - ok or not ok?
     *      - array $result['details']
     *          If ok:
     *              int $result['details']['contact_id']
     *          Otherwise:
     *              string $result['details']['error_code']
     *              string $result['details']['error_message']
     *
     * @throws waException
     */
    private function authBackendUser($params)
    {
        // some user already authorized - return it
        if (wa()->getUser()->isAuth()) {
            return [
                'status' => true,
                'details' => [
                    'contact_id' => wa()->getUser()->getId()
                ]
            ];
        }

        $m = new waWebasystIDAccessTokenManager();
        $token_info = $m->extractTokenInfo($params['access_token']);

        // it is webasyst contact id
        $contact_id = $token_info['contact_id'];

        // search backend contact in DB by webasyst_contact_id
        $cwm = new waContactWaidModel();

        // current contact id (of current installation)
        $current_contact_id = $cwm->getBoundWithWebasystContact($contact_id);


        if ($current_contact_id <= 0) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'not_bound',
                    'error_message' => _w('Not bound yet')
                ]
            ];
        }

        $contact = new waContact($current_contact_id);

        $is_existing_backend_user = $contact->exists() && $contact['is_user'] >= 1;
        if (!$is_existing_backend_user) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'access_denied',
                    'error_message' => _w("Access denied")
                ]
            ];
        }

        $contact->updateWebasystTokenParams($params);

        // last backend login datetime
        $cwm->updateById($current_contact_id, [
            'login_datetime' => date('Y-m-d H:i:s')
        ]);

        wa()->getAuth()->auth(['id' => $current_contact_id]);

        return [
            'status' => true,
            'details' => [
                'contact_id' => $current_contact_id
            ]
        ];
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
                $contact->setPhoto($path);
            }
        }
        /**
         * @event signup
         * @param waContact $contact
         */
        wa()->event('signup', $contact);
        return $contact;
    }

    /**
     * If we in process of binding and user has been asked to choose what to do (see OAuth.html)
     * @return bool
     */
    protected function isUnfinishedBindingProcess()
    {
        $post_data = $this->getRequest()->post();
        return $this->getStorage()->get('webasyst_id_server_data') && isset($post_data['renew']);
    }

    /**
     * Finish process of binding contact with webasyst ID contact
     * @param waWebasystIDAuth $auth
     * @throws waException
     */
    protected function finishBindingProcess($auth)
    {
        $post_data = $this->getRequest()->post();

        $data = $this->getStorage()->get('webasyst_id_server_data');
        $this->getStorage()->del('webasyst_id_server_data');

        // renew binding confirmed by user, unbind webasyst contact with existing contact and bind with current
        if ($post_data['renew']) {
            $auth->bindWithWebasystContact($data, true);
        }

        $this->displayAuth([]);
    }

    protected function cleanup()
    {
        $this->getStorage()->del('auth_params');
        $this->getStorage()->del('auth_app');
    }
}
