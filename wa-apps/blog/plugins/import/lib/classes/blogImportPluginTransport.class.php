<?php
/**
 *
 * @todo it's a draft
 *
 *
 */
abstract class blogImportPluginTransport /* implements Serializable*/
{

    const LOG_DEBUG = 5;
    const LOG_INFO = 4;
    const LOG_NOTICE = 3;
    const LOG_WARNING = 2;
    const LOG_ERROR = 1;
    /**
     *
     * @var waPluginSettings
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

    public function __construct($settings = null)
    {
        $this->settings = $settings;
    }

    abstract protected function initOptions();

    protected function addOption($name, $option)
    {
        if ($option) {
            if (!isset($this->options[$name])) {
                $this->options[$name] = array_merge(array(
                    'control_type' => waHtmlControl::HIDDEN,
                    'value'        => null,
                ), $option);
            } else {
                $this->options[$name] = array_merge($this->options[$name], $option);
            }
        } elseif (isset($this->options[$name])) {
            unset($this->options[$name]);
        }
    }

    private function userReplace($subject)
    {
        if (isset($this->settings['replace'])) {
            $replaces = $this->settings['replace'];
            if (!empty($replaces['search'])) {
                foreach ($replaces['search'] as $id => $search) {
                    $replace = (empty($replaces['replace']) || empty($replaces['replace'][$id])) ? '' : $replaces['replace'][$id];
                    if (empty($replaces['is_regexp']) || empty($replaces['is_regexp'][$id])) {
                        $subject = str_replace($search, $replace, $subject);
                    } else {
                        $subject = preg_replace($search, $replace, $subject);
                    }
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
            $controls[$field] = waHtmlControl::getControl($properties['control_type'], $field, array_merge($properties, $params));
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
        return $this->options;
    }

    /**
     * @param boolean $result
     * @param string[] $errors
     * @return boolean
     */
    public function validate($result, &$errors)
    {
        return $result;
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

    protected function insertPost($post)
    {
        static $post_model;
        if (!$post_model) {
            $post_model = new blogPostModel();
        }
        if (empty($post['contact_id'])) {
            $post['contact_id'] = $this->settings['contact'];
        }
        $method = __METHOD__;
        $this->log(var_export(compact('method', 'post'), true));
        $post['blog_id'] = $this->settings['blog'];
        $post['blog_status'] = $this->settings['blog_status'];
        $post['text'] = $this->userReplace($post['text']);

        $post_model->ping();
        switch ($field = $this->settings['mode']) {
            case 'title':
                if ($p = $post_model->getByField(array($field => $post[$field], 'blog_id' => $post['blog_id']))) {
                    $this->log("Post with timestamp [{$post['timestamp']}] skipped because there was a duplicate (id={$p['id']})", self::LOG_NOTICE);
                    $this->log("Post raw data ".var_export($post, true), self::LOG_DEBUG);
                    return false;
                }
                break;
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
