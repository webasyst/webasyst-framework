<?php

class installerProductReviewWidget
{
    /**
     * @var string path to template to render widget
     */
    protected $template;

    /**
     * @var int|string
     */
    protected $product_id;

    /**
     * @var waContact
     */
    protected $contact;

    /**
     * Check previous history of showing this widget (in other words widget will not be shown to often)
     * @var bool
     */
    protected $check_can_show = false;

    /**
     * @var array
     */
    protected $available_envs = ['backend'];

    /**
     * Current environment
     * @var string
     */
    protected $env;

    /**
     * @var bool
     */
    protected $is_debug;

    /**
     * installerProductReviewWidget constructor.
     *
     * @param int|string $product_id - integer ID of product as it in DB OR string ID of product in form of url in store:
     *      Kind of ids in form of store's urls:
     *          - app/{app_id} OR just {app_id}         - application
     *          - theme/{ext_id}                        - themes family
     *          - plugin/{app_id}/{ext_id}              - application plugin
     *          - plugin/payment/{ext_id}               - payment plugin
     *          - plugin/shipping/{ext_id}              - shipping plugin
     *          - plugin/sms/{ext_id}                   - sms plugin
     *          - widget/{app_id}/{ext_id}              - application widget
     *          - widget/webasyst/{ext_id}              - system widget
     *
     * @param array $options
     *      - string $options['template'] [optional]
     *              Path to template to render widget
     *
     *      - bool   $options['check_can_show'] [optional]
     *              If TRUE then check previous history of showing this widget (in other words widget will not be shown to often)
                        Default is FALSE
     *
     *      - string[] $options['available_envs'] [optional]
     *              In which environments widget is available. Default is ['backend']
     *
     *      - waContact|int $options['contact'] [optional]
     *              Current contact, default wa()->getUser()
     *
     *      - bool $options['is_debug'] [optional]
         *          In debug mode if there is errors they will be printed into js console,
         *              also widget always will render product info even if there is no license for this product
         *              but leaving review will not be working.
     *              Default is FALSE
     * @throws waException
     */
    public function __construct($product_id, $options = [])
    {
        $this->product_id = $product_id;

        $options = is_array($options) ? $options : [];

        if (isset($options['template'])) {
            $this->template = $options['template'];
        } else {
            $this->template = wa()->getAppPath('templates/helper/product_review_widget.html', 'installer');
        }

        $this->check_can_show = !empty($options['check_can_show']);

        $this->contact = $this->newContact(ifset($options['contact']));

        if (isset($options['available_envs']) && is_array($options['available_envs'])) {
            $this->available_envs = $options['available_envs'];
        }

        $this->env = wa()->getEnv();

        $this->is_debug = !empty($options['is_debug']);
    }

    /**
     * Render product review widget
     * @param array $assign - any assign vars that would be available in template
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    public function render($assign = [])
    {
        $errors = $this->checkErrors();

        $assign['store_review_core_url'] = '';
        $assign['store_auth_params'] = '';
        $assign['product_id'] = $this->product_id;
        $assign['is_debug'] = $this->is_debug;
        $assign['errors'] = $errors;

        // crucial params to connect to store only available if there are not errors
        if (!$errors) {
            $assign['store_review_core_url'] = $this->getStoreReviewCoreUrl();
            $assign['store_auth_params'] = $this->getStoreAuthParams();
        }

        $assign['has_access'] = wa()->getUser()->getRights('installer', 'backend');

        return $this->renderTemplate($this->template, $assign);
    }

    /**
     * For current contact marks widget as close we fix current datetime, so we can check latter can we show widget when pass check_can_show option
     * @param waContact $contact
     */
    public static function markAsClosed(waContact $contact)
    {
        $contact->setSettings('installer', 'close_widget_datetime', date('Y-m-d H:i:s'));
    }

    /**
     * For current contact mark when last review was added
     * @param waContact $contact
     */
    public static function markWhenReviewAdded(waContact $contact)
    {
        $contact->setSettings('installer', 'add_review_datetime', date('Y-m-d H:i:s'));
    }

    /**
     * @return null|string
     */
    protected function getStoreReviewCoreUrl()
    {
        $url = '';

        try {
            $wa_installer = installerHelper::getInstaller();
            $url = $wa_installer->getStoreReviewCoreUrl();
            $parsed_url = parse_url($url);
            $scheme = ($parsed_url['scheme'] === 'http') ? '//' : $parsed_url['scheme'].'://';
            $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
            $url = $scheme.$parsed_url['host'].$port.ifset($parsed_url['path']);
        } catch (Exception $e) {

        }

        $params = [
            'purpose' => 'product_review_widget'
        ];

        if ($this->check_can_show) {
            $params['check_can_show'] = 1;
        }

        if ($this->is_debug) {
            $params['is_debug'] = 1;
        }

        $params = array_merge($params, $this->getStoreAuthParams());

        if ($url) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    protected function getStoreAuthParams()
    {
        $params = [];
        try {
            $init_data = wa('installer')->getConfig()->getTokenData();
            $params = (array)$init_data;
            $params['locale'] = wa()->getUser()->getLocale();
        } catch (Exception $e) {
        }
        return $params;
    }

    /**
     * @param string $template
     * @param array $assign
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    protected function renderTemplate($template, $assign = [])
    {
        if (!file_exists($template)) {
            return '';
        }

        $view = wa()->getView();
        $old_vars = $view->getVars();
        $view->clearAllAssign();

        // ensure installer static url, cause widget could be called from other apps
        $assign['installer_app_static_url'] = wa()->getCdn(wa()->getAppStaticUrl('installer'));

        $view->assign($assign);

        $html = $view->fetch($template);
        $view->clearAllAssign();
        $view->assign($old_vars);

        return $html;
    }

    /**
     * Check is valid product ID which is passed to constructor
     * See constructor php-doc for details
     * @param int|string $product_id
     * @return bool
     */
    protected function isValidProductId($product_id)
    {
        if (wa_is_int($product_id) && $product_id > 0) {
            return true;
        }
        if (!is_string($product_id)) {
            return false;
        }

        $parts = explode('/', $product_id, 3);
        if (count($parts) == 1) {
            return true;            // valid {app_id} case
        }

        $type = $parts[0];
        if (!in_array($type, ['app', 'theme', 'plugin', 'widget'])) {
            return false;
        }

        if ($type === 'app' || $type === 'theme') {
            // valid app/{app_id}, theme/{ext_id} cases
            // important: theme/{app_id}/{ext_id} is not supported
            return count($parts) == 2;
        }

        if ($type === 'plugin' || $type === 'widget') {
            // valid plugin/{app_id}/{ext_id}, plugin/payment/{ext_id}, plugin/shipping/{ext_id}, plugin/sms/{ext_id} cases
            // valid widget/{app_id}/{ext_id}, widget/webasyst/{ext_id} cases
            return count($parts) == 3;
        }

        return false;   // invalid type of product
    }

    /**
     * Return waContact instance by input argument
     * If input is not valid contact then return current waUser
     * @param int|waContact|null $contact
     * @return waContact
     * @throws waException
     */
    protected function newContact($contact)
    {
        if (wa_is_int($contact) && $contact > 0) {
            $contact = new waContact($contact);
            if (!$contact->exists()) {
                $contact = new waContact(0);
            }
        } elseif (!($contact instanceof waContact)) {
            $contact = wa()->getUser();
        }
        return $contact;
    }

    /**
     * Check errors before render
     * @return array $errors
     */
    protected function checkErrors()
    {
        if (!$this->isWidgetAvailable()) {
            return [
                'not_available' => "Widget is not available"
            ];
        }

        if (!$this->canShowWidget()) {
            return [
                'cant_show_widget_often' => "Can't show widget too often"
            ];
        }

        $is_valid = $this->isValidProductId($this->product_id);
        if (!$is_valid) {
            return [
                'invalid_product_id' => 'Product id passed to widget is invalid'
            ];
        }

        return [];
    }

    /**
     * Available only on specified environments
     * @return bool
     */
    protected function isWidgetAvailable()
    {
        return in_array($this->env, $this->available_envs, true);
    }

    /**
     * Can show widget?
     * @return bool
     */
    protected function canShowWidget()
    {
        if (!$this->check_can_show) {
            return true;
        }

        $timeout = 172800;  // 48 hours
        if ($this->is_debug) {
            $timeout = 7200;    // 2 hours
        }

        $close_widget_ts = $this->getCloseWidgetTimestamp();
        if ($this->whenElapsedLessThen($close_widget_ts, $timeout)) {
            return false;
        }

        $timeout = 43200;   // 12 hours
        if ($this->is_debug) {
            $timeout = 3600;    // 1 hour
        }

        $add_review_ts = $this->getAddReviewTimestamp();
        if ($this->whenElapsedLessThen($add_review_ts, $timeout)) {
            return false;
        }

        return true;
    }

    /**
     * When last time contact add review, if not add review yet return 0
     * @return int $ts - Unix timestamp
     */
    protected function getAddReviewTimestamp()
    {
        $dt = $this->contact->getSettings('installer', 'add_review_datetime');
        return (int)$this->convertDatetimeToTimestamp($dt);
    }

    /**
     * When last time contact close widget, if not close widget yet return 0
     * @return int
     */
    protected function getCloseWidgetTimestamp()
    {
        $dt = $this->contact->getSettings('installer', 'close_widget_datetime');
        return (int)$this->convertDatetimeToTimestamp($dt);
    }

    /**
     * If elapsed since $ts less when $limit then return TRUE, otherwise FALSE
     * Method not checks input, input must be of correct types
     * @param int $ts
     * @param int $limit
     * @return bool
     */
    protected function whenElapsedLessThen($ts, $limit)
    {
        $elapsed = time() - $ts;
        return $elapsed < $limit;
    }

    /**
     * Convert datetime (Y-m-d H:i:s) to Unix timestamp
     * On failure return null
     * @param mixed $dt
     * @return null
     */
    protected function convertDatetimeToTimestamp($dt)
    {
        if (!$dt) {
            return null;
        }
        $dt = trim($dt);
        $ts = strtotime($dt);
        if (wa_is_int($ts) && $ts > 0) {
            return $ts;
        }
        return null;
    }
}
