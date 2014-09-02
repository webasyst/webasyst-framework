<?php
class webasystCreateSystemPluginCli extends waCliController
{
    public function execute()
    {
        $type = waRequest::param(0);
        $types = array('sms', 'payment', 'shipping');
        $id = waRequest::param(1);
        $params = waRequest::param();
        $pattern = '/^[a-z][a-z0-9]*$/';
        if (empty($type) || empty($params) || isset($params['help']) || !in_array($type, $types) || !preg_match($pattern, $id)) {
            $help = <<<HELP
Usage: php wa.php createSystemPlugin type plugin_id [parameters]
    type - Plugin type: shipping, payment, or sms
    plugin_id - Plugin id (string in lower case) 
Optional parameters:
    -name (Plugin name; if comprised of several words, enclose in quotes; e.g., 'My plugin')
    -version (Plugin version; e.g., 1.0.0)
    -vendor (Numerical vendor id)
    -settings (Supports user settings)
Example: php wa.php createSystemPlugin shipping myshipping -name 'My shipping' -version 1.0.0 -vendor 123456
HELP;
            print $help."\n";
        } else {
            $plugin_path = wa()->getConfig()->getPath('plugins').'/'.$type.'/'.$id;
            $this->create($type, $id, $plugin_path, $params);
        }
    }

    protected function create($type, $id, $path, $params = array())
    {
        $template_id = 'wapattern';
        $template_path = wa()->getConfig()->getPath('plugins').'/'.$type.'/'.$template_id.'/';

        if (!file_exists($path)) {
            try {
                $path .= '/';
                mkdir($path);
                // lib
                mkdir($path.'lib');
                waFiles::protect($path.'lib');
                $plugin_class = null;

                mkdir($path.'lib/classes');
                // config
                mkdir($path.'lib/config');
                // app description
                $plugin = array(
                    'name'        => empty($params['name']) ? ucfirst($id) : $params['name'],
                    'description' => '',
                    'icon'        => 'img/'.$id.'.png',
                    'version'     => empty($params['version']) ? '1.0.0' : $params['version'],
                    'vendor'      => empty($params['vendor']) ? '' : $params['vendor'],
                );

                waUtils::varExportToFile($plugin, $path.'lib/config/plugin.php');

                switch ($type) {
                    case 'payment':
                        #settings
                        if (isset($params['settings'])) {
                            waUtils::varExportToFile(array(), $path.'lib/config/settings.php');
                        }

                        #plugin class
                        $template_class_path = $template_path.'lib/'.$template_id.ucfirst($type).'.class.php';
                        $class_path = $path.'lib/'.$id.ucfirst($type).'.class.php';
                        $template = file_get_contents($template_class_path);
                        waFiles::write($class_path, str_replace($template_id, $id, $template));

                        #plugin template
                        mkdir($path.'templates');
                        waFiles::protect($path.'templates');
                        waFiles::copy($template_path.'templates/payment.html', $path.'templates/payment.html');
                        break;
                    case 'shipping':
                        #settings
                        if (isset($params['settings'])) {
                            waUtils::varExportToFile(array(), $path.'lib/config/settings.php');
                        }

                        #plugin class
                        $template_class_path = $template_path.'lib/'.$template_id.ucfirst($type).'.class.php';
                        $class_path = $path.'lib/'.$id.ucfirst($type).'.class.php';
                        $template = file_get_contents($template_class_path);
                        waFiles::write($class_path, str_replace($template_id, $id, $template));

                        break;
                    default:
                        throw new waException(sprintf("Plugin type \"%s\" not supported yet.\n", $type));
                        break;
                }

                print("Plugin with id \"{$id}\" created!\n");
            } catch (waException $ex) {
                print("Plugin with id \"{$id}\" was NOT created.\n");
                if (waSystemConfig::isDebug()) {
                    echo $ex;
                } else {
                    print "Error:".$ex->getMessage()."\n";
                }
                waFiles::delete($path);
            }
        } else {
            print("Plugin with id \"{$id}\" already exists.\n");
        }
    }

}
