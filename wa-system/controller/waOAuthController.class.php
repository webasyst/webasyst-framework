<?php

class waOAuthController extends waViewController
{
    public function execute()
    {
        $provider = waRequest::get('provider');
        $app = waRequest::get('app');
        if ($app) {
            $this->getStorage()->set('auth_app', $app);
            $params = waRequest::get();
            unset($params['app']); unset($params['provider']);
            if ($params) {
                $this->getStorage()->set('auth_params', $params);
            }
        }
        $config = wa()->getAuthConfig();
        if (isset($config['adapters'][$provider])) {
            $auth = wa()->getAuth($provider, $config['adapters'][$provider]);
            $data = $auth->auth();
            $result = $this->afterAuth($data);
            // close popup or show error
            $this->displayAuth($result);
        } else {
            throw new waException('Unknown auth provider');
        }
    }

    protected function displayAuth($result)
    {
        // display oauth popup template
        wa('webasyst');
        $this->executeAction(new webasystOAuthAction());
    }

    protected function displayError($error)
    {
        echo $error;
        exit;
    }

    /**
     * @param array $data
     * @return waContact
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
                // save new link
                $contact->save(array(
                    $data['source'].'_id' => $data['source_id']
                ));
            }
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
            $photo_url_parts = explode('/', $photo_url);
            // copy photo to tmp dir
            $path = wa()->getTempPath('auth_photo/'.$contact_id.'.'.md5(end($photo_url_parts)), $app_id);
            $s = parse_url($photo_url, PHP_URL_SCHEME);
            $w = stream_get_wrappers();
            if (in_array($s, $w) && ini_get('allow_url_fopen')) {
                $photo = file_get_contents($photo_url);
            } elseif (function_exists('curl_init')) {
                $ch = curl_init($photo_url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
                $photo = curl_exec($ch);
                curl_close($ch);
            } else {
                $photo = null;
            }
            if ($photo) {
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
}