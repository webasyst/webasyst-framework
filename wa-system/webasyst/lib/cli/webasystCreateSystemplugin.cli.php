<?php

class webasystCreateSystempluginCli extends webasystCreateCliController
{
    protected $plugin_id;
    protected $type;

    protected $template_id = 'wapattern';
    protected $template_path;

    public function showHelp()
    {
        $help = <<<HELP
Usage: php wa.php createSystemPlugin type plugin_id [parameters]
    type - Plugin type: shipping, payment, or sms
    plugin_id - Plugin id (string in lower case) 
Optional parameters:
    -name (Plugin name; if comprised of several words, enclose in quotes; e.g., 'My plugin')
    -version (Plugin version; e.g., 1.0.0)
    -vendor (Numerical vendor id)
    -settings (Supports user settings)
    
    -prototype (plugin id which will be used as prototype, by default it 'wapattern')
Example: php wa.php createSystemPlugin shipping myshipping -name 'My shipping' -version 1.0.0 -vendor 123456
HELP;
        print $help."\n";
        parent::showHelp();
    }


    protected function init()
    {
        $init = parent::init();

        $this->type = waRequest::param(0);
        $this->app_id = 'webasyst';
        $this->plugin_id = waRequest::param(1);

        $template_id = waRequest::param('prototype');
        if ($template_id) {
            $this->template_id = $template_id;
        }

        return $init && !empty($this->plugin_id);
    }


    protected function initPath()
    {
        parent::initPath();

        $plugins_path = wa()->getConfig()->getPath('plugins').'/'.$this->type.'/';

        $this->path = $plugins_path.$this->plugin_id.'/';
        $this->template_path = $plugins_path.$this->template_id.'/';
        if (!file_exists($this->template_path)) {
            throw new waException(sprintf('Not found prototype plugin %s, check -prototype option', $this->template_id));
        }
    }


    protected function verifyParams($params = array())
    {
        $errors = parent::verifyParams($params);

        if (!preg_match('@^[a-z][a-z0-9]+$@', $this->plugin_id)) {
            $errors[] = "Invalid plugin ID";
        }
        $types = array('sms', 'payment', 'shipping');
        if (!in_array($this->type, $types)) {
            $errors[] = "Invalid plugin type";
        }
        return $errors;
    }


    protected function showReport($data = array())
    {
        echo <<<REPORT
Plugin with id "$this->plugin_id" created!

REPORT;
    }

    protected function createClass($file)
    {
        $pattern = sprintf('@\b%s@', $this->plugin_id);
        $name = $this->template_path.preg_replace($pattern, $this->template_id, $file);
        if (file_exists($name)) {
            $file = file_get_contents($name);
        } else {
            $file = '<?php';
            switch ($this->type) {
                case 'sms':
                    break;
                case 'shipping':
                    break;
                case 'payment':
                    break;
            }
        }

        return str_replace($this->template_id, $this->plugin_id, $file);
    }

    protected function createConfig($params = array())
    {
        return array(
            'name'        => empty($params['name']) ? ucfirst($this->plugin_id) : $params['name'],
            'description' => '',
            'icon'        => 'img/'.$this->plugin_id.'.png',
            'version'     => empty($params['version']) ? $this->getDefaults('version') : $params['version'],
            'vendor'      => empty($params['vendor']) ? $this->getDefaults('vendor') : $params['vendor'],
        );
    }

    protected function create($params = array())
    {
        $config = $this->createConfig($params);

        $structure = array(
            'lib/classes',
            'lib/vendors',
            'lib/config/plugin.php' => $config,
            'img'
        );

        $files = array(
            'lib/'.$this->plugin_id.ucfirst($this->type).'.class.php',
        );

        switch ($this->type) {
            case 'payment':
                #settings
                if (isset($params['settings'])) {
                    $structure['lib/config/settings.php'] = array();
                }
                $structure['templates/payment.html'] = 'templates/payment.html';
                break;
            case 'shipping':
                #settings
                if (isset($params['settings'])) {
                    $structure['lib/config/settings.php'] = array();
                }
                break;
            case 'sms':
                break;
        }

        foreach ($files as $file) {
            $structure[$file] = $this->createClass($file);
        }

        $this->createStructure($structure);
        $this->protect(array('lib', 'templates');

        return $config;
    }
}
