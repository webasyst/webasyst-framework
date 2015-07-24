<?php

class webasystCreateLayoutCli extends waCliController
{
    public function execute()
    {
        if (!waRequest::param(1) || null !== waRequest::param('help')) {
            return $this->showHelp();
        }

        list($app_id, $layout) = $this->getParameters();
        $this->create($app_id, $layout);
    }

    protected function showHelp()
    {
        echo <<<HELP
Usage: php wa.php createLayout app_id layout

    Create a layout in given app, along with an HTML template for it.

Example:
    php wa.php createLayout myapp backend
        -> myappBackendLayout extends waLayout
        templates/layouts/Backend.html
HELP;
    }

    protected function create($app_id, $layout)
    {
        $files_created = array();

        // Save PHP into a file
        $layout_path = wa()->getAppPath('lib/layouts/', $app_id);
        $layout_filename = $app_id.ucfirst($layout).'.layout.php';
        waFiles::create($layout_path);
        file_put_contents($layout_path.$layout_filename, $this->getPhp($app_id, $layout));
        $files_created[] = $layout_path.$layout_filename;

        // Save template into a file
        $template_path = wa()->getAppPath('templates/layouts/'.ucfirst($layout).'.html', $app_id);
        waFiles::create($template_path);
        file_put_contents($template_path, $this->getHtml($app_id, $layout));
        $files_created[] = $template_path;

        print "Successfully created the following files:\n".join("\n", $files_created);
    }

    protected function getPhp($app_id, $layout)
    {
        $class_name = $app_id . ucfirst($layout) . 'Layout';
        $result = "<?php\nclass {$class_name} extends waLayout\n{\n%CLASS_CONTENT%\n}\n";
        $result = str_replace('%CLASS_CONTENT%', "\tpublic function execute()\n\t{\n\t\t// !!! TODO\n\t}", $result);
        $result = str_replace("\t", "    ", $result);
        return $result;
    }

    protected function getHtml($app_id, $layout)
    {
        return '<!DOCTYPE html><html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{$wa->title()|default:$wa->appName()|escape} — {$wa->accountName()}</title>
    {$wa->css()}
    <link href="{$wa_app_static_url}css/'.$app_id.'.css?v{$wa->version()}" rel="stylesheet" type="text/css" />
    <script src="{$wa_url}wa-content/js/jquery/jquery-1.11.1.min.js" type="text/javascript"></script>
    <script src="{$wa_url}wa-content/js/jquery-wa/wa.core.js" type="text/javascript"></script>
    <script type="text/javascript" src="{$wa_app_static_url}js/'.$app_id.'.js?{$wa->version()}"></script>
    {$wa->js()}
</head>
<body>
<div id="wa">
    {$wa->header()}
    <div id="wa-app">
        <div id="maincontent">
            {$content}
        </div>
    </div>
</div>
</body>
</html>';
    }

    protected function getParameters()
    {
        $app_id = strtolower(waRequest::param(0));
        $layout = strtolower(waRequest::param(1));
        if (!wa()->appExists($app_id)) {
            $this->dieWithErrors(array(
                'App '.$app_id.' does not exist',
            ));
        }
        if (!preg_match('~^[a-z][a-z0-9_]*$~', $layout)) {
            $this->dieWithErrors(array(
                'Incorrect layout name: '.$layout,
            ));
        }
        return array($app_id, $layout);
    }

    protected function dieWithErrors($errors)
    {
        print "ERROR:\n";
        print implode("\n", $errors);
        exit;
    }
}

