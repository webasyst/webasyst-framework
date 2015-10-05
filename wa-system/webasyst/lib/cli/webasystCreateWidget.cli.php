<?php

class webasystCreateWidgetCli extends webasystCreateCliController
{
    protected $widget_id = false;

    protected function showHelp()
    {
        echo <<<HELP
Usage: php wa.php createWidget [app_id] [widget_id] [parameters]
    app_id - App id (string in lower case)
    widget_id - Widget id (string in lower case)
Optional parameters:
    -name (Plugin name; if comprised of several words, enclose in quotes; e.g., 'My widget')
    -version (Plugin version; e.g., 1.0.0)
    -vendor (Numerical vendor id)
    -settings (has settings)
Example: php wa.php createWidget someapp mywidget -name 'My widget' -version 1.0.0 -vendor 123456 -settings
HELP;
        parent::showHelp();
    }

    protected function verifyParams($params = array())
    {
        $errors = array();
        if (!preg_match('@^[a-z][a-z0-9]+$@', $this->widget_id)) {
            $errors[] = "Invalid widget ID";
        }
        return $errors;
    }

    protected function create($params = array())
    {
        $config = array(
            'name'    => ifempty($params['name'], $this->widget_id),
            'size'    => array('2x2', '2x1', '1x1'),
            'img'     => "img/{$this->widget_id}.png",
            'version' => ifempty($params['version'], $this->getDefaults('version')),
            'vendor'  => ifempty($params['vendor'], $this->getDefaults('vendor')),
        );

        if ($this->app_id == 'webasyst') {
            $name = $this->widget_id;
        } else {
            $name = $this->app_id.ucfirst($this->widget_id);
        }

        $paths = array(
            "img/{$this->widget_id}.png",
            'lib/config/widget.php'  => $config,
            "lib/{$name}.widget.php" => $this->getWidgetCode($name),
            'templates/Default.html' => $this->getTemplateCode(),
        );
        if (isset($params['settings'])) {
            $paths = array_merge(
                $paths,
                array(
                    'lib/config/settings.php' => array(),
                )
            );
        }
        $this->createStructure($paths);
        $protected_paths = array(
            'lib/',
            'templates/',
        );
        $this->protect($protected_paths);
        return $config;
    }


    protected function init()
    {
        $init = parent::init();
        $this->widget_id = waRequest::param(1);
        return $init && !empty($this->widget_id);
    }

    protected function initPath()
    {
        parent::initPath();
        if ($this->app_id == 'webasyst') {
            $this->path = wa()->getConfig()->getPath('widgets').'/'.$this->widget_id.'/';
        } else {
            $this->path = wa()->getAppPath('widgets/'.$this->widget_id, $this->app_id).'/';
        }
    }


    protected function showReport($data = array())
    {
        echo <<<REPORT
Widget with id "$this->widget_id" created!

Useful commands:

    #generate widget's locale files
    php wa-system/locale/locale.php $this->app_id/widgets/$this->widget_id
REPORT;
    }

    private function getTemplateCode()
    {
        return '<div class="block">{$message|escape}</div>';

    }

    private function getWidgetCode($name)
    {
        $class = $name.'Widget';
        return <<<PHP
<?php

class {$class} extends waWidget
{
    public function defaultAction()
    {
        \$this->display(array(
            'message' => 'Hello world!',
            'info' => \$this->getInfo()
        ));
    }
}
PHP;

    }
}