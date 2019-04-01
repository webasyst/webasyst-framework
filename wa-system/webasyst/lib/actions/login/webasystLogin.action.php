<?php

class webasystLoginAction extends waBackendLoginAction
{
    public function execute()
    {
        $this->assign('title', $this->getTitle());
        $this->assign('title_style', $this->getTitleStyle());

        $this->view->setOptions(array('left_delimiter' => '{', 'right_delimiter' => '}'));
        if ($this->template === null) {
            $this->template = 'Login.html';
            $template_file = wa()->getDataPath('templates/'.$this->template, false, 'webasyst');
            if (file_exists($template_file)) {
                $this->template = 'file:'.$template_file;
            } else {
                $this->template = wa()->getAppPath('templates/actions/login/', 'webasyst') . $this->template;
            }
        }


        $this->view->assign(array(
            'login' => waRequest::post('login', $this->getStorage()->read('auth_login'))
        ));

        parent::execute();

        if ($this->layout) {
            $this->layout->assign('error', $this->view->getVars('error'));
        }

        $ref = waRequest::server('HTTP_REFERER');
        if(waRequest::get('back_to') && $ref) {
            $this->getStorage()->write('login_back_on_cancel', $ref);
        } else if (!$ref) {
            $this->getStorage()->remove('login_back_on_cancel');
        }
        $this->assign('back_on_cancel', wa()->getStorage()->read('login_back_on_cancel'));

    }

    protected function afterAuth()
    {
        $this->getStorage()->remove('auth_login');
        $redirect = $this->getConfig()->getCurrentUrl();
        $backend_url = $this->getConfig()->getBackendUrl(true);
        if (!$redirect || $redirect === $backend_url) {
            $redirect = $this->getUser()->getLastPage();
        }
        if (!$redirect || substr($redirect, 0, strlen($backend_url) + 1) == $backend_url.'?') {
            $redirect = $backend_url;
        }

        wa()->getUser()->setSettings('webasyst', 'backend_url', $this->getConfig()->getHostUrl() . $backend_url);

        $result = $this->throwLoginEvent(array(
            'redirect_url' => $redirect
        ));

        $this->redirect(array(
            'url' => $result['redirect_url']
        ));
    }

    /**
     * Throw event about successful log-in and then process it
     *
     * 1. Event throwing step:
     *   Name of event will be 'backend_login'
     *   App id of event will be 'webasyst'
     *
     * 2. Processing event result
     *   a. Check all redirect urls and choose first not looks like original redirect url (just simple === for now)
     *   b.
     *   c.
     *   ...
     *
     * @param array $params Input params of event
     *   - 'redirect_url' Original url where login action going to redirect
     *
     * @return array
     *   - 'redirect_url' - always presented - chosen redirect url
     *
     */
    protected function throwLoginEvent($params = array())
    {
        // typecast input options
        $params = is_array($params) ? $params : array();

        /**
         * Event after successful logging in webasyst backend
         *
         * @event backend_login
         * @app_id webasyst (system)
         * @param array
         *   - 'redirect_url' Original url where login action going to redirect
         *
         * @return array
         *   Each handler expected return array of this format
         *     - string|null 'redirect_url' - Optional url where need to redirect there
         */
        $results = wa()->event(array('webasyst', 'backend_login'), $params);

        // extract original redirect url
        $original_redirect_url = isset($params['redirect_url']) && is_scalar($params['redirect_url']) ? (string)$params['redirect_url'] : null;

        // result of processing
        $process_result = array(
            'redirect_url' => null
        );

        foreach ($results as $res) {
            if (is_array($res)) {
                $redirect_url = !empty($res['redirect_url']) && is_scalar($res['redirect_url']) ? (string)$res['redirect_url'] : null;
                if ($redirect_url !== $original_redirect_url) {
                    $process_result['redirect_url'] = $redirect_url;
                    break;
                }
            }
        }

        if ($process_result['redirect_url'] === null) {
            $process_result['redirect_url'] = $original_redirect_url;
        }

        $redirect_url = $process_result['redirect_url'];

        if (substr($redirect_url, 0, 5) !== 'http:' && substr($redirect_url, 0, 6) !== 'https:') {
            $host = $this->getConfig()->getHostUrl();
            $redirect_url = $host . '/' . ltrim($redirect_url, '/');
        }

        $process_result['redirect_url'] = $redirect_url;

        return $process_result;
    }

    public function getTitle()
    {
        if ( ( $title = $this->getConfig()->getOption('login_form_title'))) {
            return waLocale::fromArray($title);
        }
        return wa()->getSetting('name', 'Webasyst', 'webasyst');
    }

    public function getTitleStyle()
    {
        return $this->getConfig()->getOption('login_form_title_style');
    }

    /**
     * @param array $options
     * @return waBackendLoginForm
     */
    protected function getFormRenderer($options = array())
    {
        $base_options = wa('webasyst')->getConfig()->getOption('backend_form_renderer_options');
        if ($base_options && is_array($base_options)) {
            $options = array_merge($base_options, $options);
        }

        $request_url = trim(wa()->getConfig()->getRequestUrl(true, true), '/');
        $is_api_oauth = $request_url === 'api.php/auth';
        $options['is_api_oauth'] = $is_api_oauth;

        return parent::getFormRenderer($options);
    }
}
