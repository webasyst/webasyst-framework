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
    plugin_id - Plugin ID (string in lower case)
Optional parameters:
    -name (plugin name; if comprised of several words, enclose in quotes; e.g., 'My plugin')
    -version (plugin version; e.g., 1.0.0)
    -vendor (numerical vendor id)
    -settings (supports user settings)
    -db (supports plugin's database tables)

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


    protected function showReport($data = array(), $params = array())
    {
        $report = <<<REPORT
Plugin with id "$this->plugin_id" created!

REPORT;
        $report .= <<<REPORT
Useful commands:
    # generate plugin's locale files
    php wa.php locale wa-plugins/{$this->type}/{$this->plugin_id}
REPORT;
        if (isset($params['db'])) {
            $report .= <<<REPORT

    # generate plugin's database description file db.php
    php wa.php generateDb wa-plugins/{$this->type}/{$this->plugin_id}

REPORT;

        }
        $report .= "\n\n".<<<REPORT
    #check & compress plugin code for store
    php wa.php compress wa-plugins/{$this->type}/{$this->plugin_id}
REPORT;
        echo $report;
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

    protected function createConfig()
    {
        return array(
            'name'        => empty($params['name']) ? ucfirst($this->plugin_id) : $params['name'],
            'description' => '',
            'img'         => 'img/'.$this->plugin_id.'.png',
            'version'     => empty($params['version']) ? $this->getDefaults('version') : $params['version'],
            'vendor'      => empty($params['vendor']) ? $this->getDefaults('vendor') : $params['vendor'],
        );
    }

    protected function create($params = array())
    {
        $config = $this->createConfig();

        $structure = array(
            'lib/classes',
            'lib/vendors',
            'lib/config/plugin.php' => $config,
            //TODO add plugin's images
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
                #db
                if (isset($params['db'])) {
                    $structure['lib/config/db.php'] = array();
                }
                $structure['templates/payment.html'] = 'templates/payment.html';
                break;
            case 'shipping':
                #settings
                if (isset($params['settings'])) {
                    $structure['lib/config/settings.php'] = array();
                }
                #db
                if (isset($params['db'])) {
                    $structure['lib/config/db.php'] = array();
                }
                break;
            case 'sms':
                break;
        }

        foreach ($files as $file) {
            $structure[$file] = $this->createClass($file);
        }

        $this->createStructure($structure);
        $this->protect(array('lib', 'lib/config'));

        return $config;
    }
}
