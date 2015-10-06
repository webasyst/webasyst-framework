<?php

class blogImportPluginBackendRunController extends waLongActionController
{

    /**
     *
     * @var blogImportPlugin
     */
    private $plugin;
    /**
     *
     * @var blogImportPluginTransport
     */
    private $transport;
    private $errors = array();

    protected function preInit()
    {
        return parent::preInit();
    }

    public function execute()
    {
        try {
            parent::execute();
        } catch (waException $ex) {
            echo json_encode(array('error' => $ex->getMessage(), 'errors' => $this->errors));
        }
    }

    private function initPlugin()
    {
        $this->plugin = wa()->getPlugin('import');
    }

    protected function init()
    {
        $transport = ucfirst($this->getRequest()->post('blog_import_transport', '', waRequest::TYPE_STRING_TRIM));
        $class = "blogImportPlugin{$transport}Transport";
        if ($transport && class_exists($class)) {

            $plugin_namespace = $this->getApp().'_import';
            $namespace = $plugin_namespace.'_'.strtolower($transport);
            $this->initPlugin();
            if ($post = $this->getRequest()->post($plugin_namespace)) {
                $this->plugin->saveSettings($post);
                if (!$this->plugin->validateSettings($this->errors)) {
                    throw new waException(_wp('Invalid replace settings'));
                }
            }

            $settings = $this->plugin->getSettings();
            $blog_model = new blogBlogModel();
            if ($settings['blog'] && ($blog = $blog_model->getById($settings['blog']))) {
                $settings['blog_status'] = $blog['status'];
            } else {
                throw new waException(_wp("Target blog not found"));
            }
            $author_has_rights = false;
            try {
                if ($settings['contact']) {
                    $author_has_rights = blogHelper::checkRights($settings['blog'], $settings['contact']);
                }
            } catch (waRightsException $ex) {
                ; //do nothing
            }

            if (!$author_has_rights) {
                throw new waException(_wp("Author not found or has insufficient rights"));
            }
            $this->data['transport'] = new $class($settings);
            $this->data['blog'] = $this->plugin->getSettingValue('blog');

            $this->getTransport();
            $this->transport->setup($this->getRequest()->post($namespace, array()));
            if (!$this->transport->validate(true, $this->errors)) {
                throw new waException(_wp('Invalid settings'));
            }
            //$this->data['runtime_settings'] =$this->transport->get
            $this->data['posts'] = $this->transport->getPosts();
            $this->data['current'] = 0;
            $this->data['count'] = count($this->data['posts']);
        } else {
            throw new waException(sprintf(_wp("Transport type %s not found"), $transport));
        }
    }

    protected function step()
    {
        if ($post_id = current($this->data['posts'])) {
            $this->transport->importPost($post_id);
            ++$this->data['current'];
            array_shift($this->data['posts']);
        }
        $this->getStorage()->close();
        return !empty($this->data['posts']);
    }

    protected function finish($filename)
    {
        $this->info();
        return $this->getRequest()->post('cleanup') ? true : false;
    }

    protected function isDone()
    {
        return (count($this->data['posts']) == 0);
    }

    protected function info()
    {
        echo json_encode(array(
            'processId' => $this->processId,
            'progress'  => (isset($this->data['count']) && $this->data['count']) ? sprintf('%0.2f%%', 100.0 * $this->data['current'] / $this->data['count']) : false,
            'ready'     => $this->isDone(),
            'count'     => empty($this->data['count']) ? false : $this->data['count'],
            'blog'      => $this->data['blog'],
        ));
    }

    protected function restore()
    {
        $this->getTransport()->restore();
    }

    /**
     * @return blogImportPluginTransport
     */
    private function getTransport()
    {
        return $this->transport = &$this->data['transport'];
    }
}
