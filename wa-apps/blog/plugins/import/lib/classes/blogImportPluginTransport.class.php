<?php
/**
 *
 * @todo it's a draft
 *
 *
 */
abstract class blogImportPluginTransport
{

    const LOG_DEBUG = 5;
    const LOG_INFO = 4;
    const LOG_NOTICE = 3;
    const LOG_WARNING = 2;
    const LOG_ERROR = 1;
    /**
     *
     * @var blogPlugin
     */
    protected $settings;
    /**
     *
     * Target blog data
     * @var array()
     */
    protected $blog;
    protected $options = array();
    protected $log_level = self::LOG_INFO;


    private $url_user = '';
    private $url_pass = '';
    protected $xmlrpc_url = false;
    protected $xmlrpc_path = '';

    public function __construct($settings = null)
    {
        $this->settings = $settings;
    }


    abstract protected function initOptions();

    protected function userReplace($subject)
    {
        if (($replaces = $this->option('replace')) && !empty($replaces['search'])) {
            foreach ($replaces['search'] as $id => $search) {
                $replace = (empty($replaces['replace']) || empty($replaces['replace'][$id])) ? '' : $replaces['replace'][$id];
                if (empty($replaces['is_regexp']) || empty($replaces['is_regexp'][$id])) {
                    $subject = str_replace($search, $replace, $subject);
                } else {
                    $subject = preg_replace($search, $replace, $subject);
                }
            }
        }
        return $subject;
    }

    public function getControls($namespace = '')
    {
        $this->initOptions();
        $controls = array();

        $params = array();
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '<div class="name">%s</div>';
        $params['description_wrapper'] = '<br><span class="hint">%s</span><br>';
        $params['control_separator'] = '</div><br><div class="value no-shift">';

        $params['control_wrapper'] = <<<HTML
<div class="field">
%s
<div class="value no-shift">
	%s
	%s
</div>
</div>
HTML;
        foreach ($this->options as $field => $properties) {
            $controls[$field] = waHtmlControl::getControl($properties['settings_html_function'], $field, array_merge($properties, $params));
        }
        return $controls;
    }

    protected $response = array();


    protected function log($message, $level = self::LOG_WARNING)
    {
        static $path;
        if (!$path) {
            $path = wa()->getApp().'-import-';
        }
        if ($level <= $this->log_level) {
            waLog::log($message, $path.'common.log');
        } elseif (wa()->getConfig()->isDebug()) {
            waLog::log($message, $path.'debug.log');
        }
    }

    public function setup($runtime_settings = array())
    {
        $this->log(__METHOD__.'('.var_export($runtime_settings, true).')', self::LOG_DEBUG);
        if (!$this->options) {
            $this->initOptions();
        }
        foreach ($runtime_settings as $field => $value) {
            if (isset($this->options[$field])) {
                $this->options[$field]['value'] = $value;
            }
        }

        if (!empty($runtime_settings['debug'])) {
            $this->log_level = self::LOG_DEBUG;
        }

        $url = $this->setUrl($this->option('url', $this->xmlrpc_url), $this->xmlrpc_path);
        $this->log("Begin import from {$url['host']}", self::LOG_INFO);
    }

    protected function option($name, $default = null)
    {
        return (isset($this->options[$name]) && isset($this->options[$name]['value'])) ? $this->options[$name]['value'] : $default;
    }

    /**
     *
     * @return array[]mixed List of posts
     */
    abstract public function getPosts();

    /**
     *
     * @param mixed $source_post_id ID of imported post entry
     */
    abstract public function importPost($source_post_id);

    public function restore()
    {
        $this->log("Restore process", self::LOG_INFO);
    }

    protected function setUrl($url, $path = '')
    {

        if ($url && ($parsed_url = @parse_url($url))) {
            $this->xmlrpc_url = preg_replace('@/?$@', $path, $url, 1);
        } else {
            throw new waException(_wp("Invalid URL"));
        }
        if (!empty($parsed_url['user'])) {
            $this->url_user = $parsed_url['user'];
        }
        if (!empty($parsed_url['pass'])) {
            $this->url_pass = $parsed_url['user'];
        }
        return $parsed_url;
    }

    /**
     *
     * Call XML RPC method
     * @param string $method
     * @param mixed|null $args
     * @param mixed|null $_
     * @throws waException
     * @return mixed
     */
    protected function xmlrpc($method, $args = null, $_ = null)
    {
        static $client;
        $params = func_get_args();
        $method = array_shift($params);
        if ((count($params) == 1) && is_array(current($params))) {
            $params = current($params);
        }

        $this->log(__METHOD__."({$method}) \n".var_export($params, true), self::LOG_DEBUG);
        if (extension_loaded('curl')) {
            require_once(dirname(__FILE__).'/../../vendors/xmlrpc/lib/init.php');
        }
        if (class_exists('xmlrpc_client')) {

            if (!isset($client)) {
                $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
                $client = new xmlrpc_client($this->xmlrpc_url);
                if ($this->url_pass || $this->url_user) {
                    $client->SetCurlOptions(array(CURLOPT_USERPWD => "{$this->url_user}:{$this->url_pass}"));
                }
                $client->request_charset_encoding = 'utf-8';
            }

            $this->log(__METHOD__."({$method}) external lib", self::LOG_DEBUG);

            $request = new xmlrpcmsg($method, array(php_xmlrpc_encode($params)));
            $response = $client->send($request);

            if ($response && ($fault_code = $response->faultCode())) {
                $fault_string = $response->faultString();
                $this->log(__METHOD__."({$method}) returns {$fault_string} ({$fault_code})", self::LOG_ERROR);
                $this->log(__METHOD__.$response->raw_data, self::LOG_DEBUG);
                throw new waException("{$fault_string} ({$fault_code})", $fault_code);
            }
            return php_xmlrpc_decode($response->value(), array('dates_as_objects' => true));

        } else {
            if (!extension_loaded('xmlrpc')) {
                throw new waException("PHP extension 'xmlrpc' or 'curl' required.");
            }
            $this->log(__METHOD__."({$method}) internal PHP lib", self::LOG_DEBUG);

            $request = xmlrpc_encode_request($method, $params, array('encoding' => 'utf-8'));

            $request_options = array(
                'method'  => "POST",
                'header'  => "Content-Type: text/xml".(($this->url_pass || $this->url_user) ? "\nAuthorization: Basic ".base64_encode("{$this->url_user}:$this->url_pass") : ''),
                'content' => $request,
            );
            $context = stream_context_create(array('http' => $request_options));

            //TODO add curl support
            $retry = 0;
            do {
                ob_start();
                if ($retry) {
                    sleep(6);
                }
                $file = file_get_contents($this->xmlrpc_url, false, $context);
                $log = ob_get_clean();
                if ($log) {
                    $this->log(__METHOD__.":\t{$log}", self::LOG_WARNING);
                }
                if (!$file) {
                    $this->log(__METHOD__."({$method}) fail open stream", self::LOG_ERROR);
                }
            } while (!$file && (++$retry < 10));
            if (!$file) {
                $this->log('Fail while request WordPress', self::LOG_ERROR);
                throw new waException(sprintf(_wp("I/O error: %s"), 'Fail while request WordPress'));
            }
            $response = xmlrpc_decode($file, 'utf-8');
            $this->log(__METHOD__."({$method}) \n".var_export(compact('response','file'), true), self::LOG_DEBUG);
            if ($response && xmlrpc_is_fault($response)) {
                $this->log(__METHOD__."({$method}) returns {$response['faultString']} ({$response['faultCode']})", self::LOG_ERROR);
                throw new waException("{$response['faultString']} ({$response['faultCode']})", $response['faultCode']);
            }
        }
        return $response;
    }

    protected function insertPost($post)
    {
        static $post_model;
        if (!$post_model) {
            $post_model = new blogPostModel();
        }
        if (empty($post['contact_id'])) {
            $post['contact_id'] = $this->settings['contact'];
        }
        $post['blog_id'] = $this->settings['blog'];
        $post['blog_status'] = $this->settings['blog_status'];

        $post_model->ping();
        switch ($field = $this->settings['mode']) {
            case 'title':
            {
                if ($p = $post_model->getByField(array($field => $post[$field], 'blog_id' => $post['blog_id']))) {
                    $this->log("Post with timestamp [{$post['timestamp']}] skipped because there was a duplicate (id={$p['id']})", self::LOG_NOTICE);
                    $this->log("Post raw data ".var_export($post, true), self::LOG_DEBUG);
                    return false;
                }
                break;
            }
        }
        if ($post['blog_status'] == blogBlogModel::STATUS_PUBLIC) {
            $post['url'] = $post_model->genUniqueUrl(empty($post['url']) ? $post['title'] : $post['url']);

        } elseif (!empty($post['url'])) {
            $post['url'] = $post_model->genUniqueUrl($post['url']);
        } else {
            $post['url'] = '';
        }

        $post['id'] = $post_model->updateItem(null, $post);
        return $post;
    }

    protected function getContactByEmail($emails = array())
    {
        static $model;
        $sql = <<<SQL
SELECT
    wa_contact.id AS id,
    LOWER(wa_contact_emails.email) AS email
FROM
    wa_contact
JOIN
    wa_contact_emails
ON
    (wa_contact_emails.contact_id = wa_contact.id)
WHERE
    (wa_contact.is_user = 1)
AND
    (LOWER(wa_contact_emails.email) IN (s:emails))
SQL;

        if (!isset($model)) {
            $model = new waModel();
        }
        $model->ping();
        $contacts = array();
        if ($emails && ($contacts = $model->query($sql, array('emails' => $emails))->fetchAll('id', true))) {
            foreach ($contacts as $id => $email) {
                $contacts[$email] = $id;
            }
        }
        return $contacts;
    }
}
