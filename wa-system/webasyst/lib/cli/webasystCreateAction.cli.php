<?php

class webasystCreateActionCli extends waCliController
{
    protected static $class_names = array(
        'action'  => 'waViewAction',
        'actions' => 'waViewActions',
        'long'    => 'waLongActionController',
        'json'    => 'waJsonController',
        'jsons'   => 'waJsonActions',
    );

    public function execute()
    {
        if (!waRequest::param(2) || null !== waRequest::param('help')) {
            return $this->showHelp();
        }

        list($app_id, $module, $action_type, $action_names) = $this->getParameters();
        $this->create($app_id, $module, $action_type, $action_names);
    }

    protected function showHelp()
    {
        echo <<<HELP
Usage: php wa.php createAction app_id module [action_type] action_names

    Create an action or controller in given app, along with an HTML template(s)
    if appropriate for given controller type.

Action type be one of:
    action  waViewAction // default if omitted
    actions waViewActions
    long    waLongActionController
    json    waJsonController
    jsons   waJsonActions

Examples:
    php wa.php createAction shop examples first
        -> shopExamplesFirstAction extends waViewAction
        templates/actions/examples/ExamplesFirst.html

    php wa.php createAction shop examples actions first second third
        -> shopExamplesActions extends waViewActions
        templates/actions/examples/ExamplesFirst.html
        templates/actions/examples/ExamplesSecond.html
        templates/actions/examples/ExamplesThird.html

    php wa.php createAction shop examples json first
        -> shopExamplesFirstController extends waJsonController

    php wa.php createAction shop examples default
        -> shopExamplesAction extends waViewAction
        templates/actions/examples/Examples.html
HELP;
    }

    protected function create($app_id, $module, $action_type, $action_names)
    {
        $files_created = array();

        // Generate PHP controller contents
        $php_wrap = $this->getPhpWrap($app_id, $module, $action_type, $action_names);
        $php_inner = $this->getPhpInner($action_type, $action_names);
        $php = str_replace('%CLASS_CONTENT%', $php_inner, $php_wrap);

        // Save PHP controller into a file
        $action_path = wa()->getAppPath('lib/actions/'.$module.'/', $app_id);
        $action_filename = $this->getPhpFilename($app_id, $module, $action_type, $action_names);
        waFiles::create($action_path);
        if (!file_exists($action_path.$action_filename)) {
            file_put_contents($action_path.$action_filename, $php);
            $files_created[] = $action_path.$action_filename;
        } else {
            print sprintf("File already exists: %s\n", $action_path.$action_filename);
        }

        // Save templates
        if ($action_type == 'action' || $action_type == 'actions') {
            $template_path = wa()->getAppPath('templates/actions/'.$module.'/', $app_id);
            waFiles::create($template_path);
            foreach($action_names as $action_name) {
                $template_filename = $this->getTemplateFilename($module, $action_type, $action_name);
                if (!file_exists($template_path.$template_filename)) {
                    file_put_contents($template_path.$template_filename, "<h1>Hello, World!</h1> <!-- !!! TODO FIXME -->\n\n<p>{$action_path}{$action_filename}</p>\n<p>{$template_path}{$template_filename}</p>");
                    $files_created[] = $template_path.$template_filename;
                } else {
                    print sprintf("File already exists: %s\n", $template_path.$template_filename);
                }
            }
        }

        if ($files_created) {
            print "Successfully created the following files:\n".join("\n", $files_created);
        } else {
            print "Nothing changed.";
        }
    }

    protected function getPhpWrap($app_id, $module, $action_type, $action_names)
    {
        $parent_class_name = self::$class_names[$action_type];
        switch($action_type) {
            case 'jsons':
            case 'actions':
                $class_name = $app_id . ucfirst($module) . 'Actions';
                break;
            default: // json jsons action long
                $class_name = $app_id . ucfirst($module);
                if ($action_names[0] != 'default') {
                    $class_name .= ucfirst($action_names[0]);
                }
                if ($action_type == 'action') {
                    $class_name .= 'Action';
                } else {
                    $class_name .= 'Controller';
                }
                break;
        }
        return "<?php\nclass {$class_name} extends {$parent_class_name}\n{\n%CLASS_CONTENT%\n}\n";
    }

    protected function getPhpInner($action_type, $action_names)
    {
        $methods = array();
        switch($action_type) {
            case 'jsons':
            case 'actions':
                foreach($action_names as $action_name) {
                    $methods[] = 'protected function '.$action_name.'Action()';
                }
                break;
            case 'long':
                $methods[] = 'protected function init()';
                $methods[] = 'protected function step()';
                $methods[] = 'protected function isDone()';
                $methods[] = 'protected function finish($filename)';
                $methods[] = 'protected function info()';
                break;
            default:
                $methods[] = 'public function execute()';
                break;
        }

        $result = array();
        foreach($methods as $m) {
            $result[] = "\t{$m}\n\t{\n\t\t// !!! TODO\n\t}";
        }

        return str_replace("\t", "    ", join("\n\n", $result));
    }

    protected function getPhpFilename($app_id, $module, $action_type, $action_names)
    {
        switch($action_type) {
            case 'jsons':
            case 'actions':
                return $app_id . ucfirst($module) . '.actions.php';
            default: // json jsons action long
                $file_name = $app_id . ucfirst($module);
                if ($action_names[0] != 'default') {
                    $file_name .= ucfirst($action_names[0]);
                }
                if ($action_type == 'action') {
                    $file_name .= '.action.php';
                } else {
                    $file_name .= '.controller.php';
                }
                return $file_name;
        }
    }

    protected function getTemplateFilename($module, $action_type, $action_name)
    {
        $result = ucfirst($module);
        if ($action_type != 'action' || $action_name != 'default') {
            $result .= ucfirst($action_name);
        }
        $result .= '.html';
        return $result;
    }

    protected function getParameters()
    {
        $app_id = strtolower(waRequest::param(0));
        $module = strtolower(waRequest::param(1));
        if (!wa()->appExists($app_id)) {
            $this->dieWithErrors(array(
                'App '.$app_id.' does not exist',
            ));
        }
        if (!preg_match('~^[a-z][a-z0-9_]*$~', $module)) {
            $this->dieWithErrors(array(
                'Incorrect module name: '.$module,
            ));
        }

        // Get action type and names
        if (!waRequest::param(3)) {
            $action_type = 'action';
            $action_names = array(strtolower(waRequest::param(2)));
            if (!preg_match('~^[a-z][a-z0-9_]*$~', $action_names[0])) {
                $this->dieWithErrors(array(
                    'Incorrect action name: '.$action_names[0],
                ));
            }
        } else {
            $action_type = strtolower(waRequest::param(2));
            if (empty(self::$class_names[$action_type])) {
                $this->dieWithErrors(array(
                    'Unknown action type: '.$action_type,
                ));
            }

            $action_names = array();
            for ($i = 3; waRequest::param($i); $i++) {
                $action_name = strtolower(waRequest::param($i));
                if (!preg_match('~^[a-z][a-z0-9_]*$~', $action_name)) {
                    $this->dieWithErrors(array(
                        'Incorrect action name: '.$action_name,
                    ));
                }
                $action_names[] = $action_name;
                if ($action_type != 'jsons' && $action_type != 'actions') {
                    break;
                }
            }
        }

        if (empty($action_names)) {
            $this->dieWithErrors(array(
                'Specify at least one action name',
            ));
        }

        return array($app_id, $module, $action_type, $action_names);
    }

    protected function dieWithErrors($errors)
    {
        print "ERROR:\n";
        print implode("\n", $errors);
        exit;
    }

}
