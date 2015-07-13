<?php

class webasystCreateModelCli extends waCliController
{
    public function execute()
    {
        if (!waRequest::param(1) || null !== waRequest::param('help')) {
            return $this->showHelp();
        }

        list($app_id, $class_name, $table) = $this->getParameters();
        $this->create($app_id, $class_name, $table);
    }

    protected function showHelp()
    {
        echo <<<HELP
Usage: php wa.php createModel app_id db_table_name

    Create a model in given app for given mysql table.

Example:
    php wa.php createModel myapp myapp_records

See also:
    php wa.php generateDb --help
HELP;
    }

    protected function create($app_id, $class_name, $table)
    {
        $files_created = array();

        // Save PHP into a file
        $path = wa()->getAppPath('lib/model/', $app_id);
        if (!file_exists($path)) {
            // shop and helpdesk use `model` dir instead of `models` for some reason
            $path = wa()->getAppPath('lib/models/', $app_id);
        }
        $filename = preg_replace('~Model$~', '', $class_name).'.model.php';
        waFiles::create($path);
        file_put_contents($path.$filename, $this->getPhp($app_id, $class_name, $table));
        $files_created[] = $path.$filename;

        print "Successfully created:\n".join("\n", $files_created);
    }

    protected function getPhp($app_id, $class_name, $table)
    {
        $result = "<?php\nclass {$class_name} extends waModel\n{\n%CLASS_CONTENT%\n}\n";
        $result = str_replace('%CLASS_CONTENT%', "\tprotected \$table = '{$table}';", $result);
        $result = str_replace("\t", "    ", $result);
        return $result;
    }

    protected function getParameters()
    {
        $app_id = strtolower(waRequest::param(0));
        $table = strtolower(waRequest::param(1));
        if (!wa()->appExists($app_id)) {
            $this->dieWithErrors(array(
                'App '.$app_id.' does not exist',
            ));
        }
        if (!preg_match('~^[a-z][a-z0-9_]*$~', $table)) {
            $this->dieWithErrors(array(
                'Incorrect table name: '.$table,
            ));
        }
        if (!preg_match('~^'.preg_quote($app_id, '~').'_~', $table)) {
            $table = $app_id.'_'.$table;
            echo "WARNING: table name must start with an app_id prefix. Going on with '{$table}'.\n";
        }

        $class_name = array($app_id);
        foreach(explode('_', preg_replace('~^'.preg_quote($app_id, '~').'_~', '', $table)) as $part) {
            $class_name[] = ucfirst($part);
        }
        $class_name = join('', $class_name).'Model';
        return array($app_id, $class_name, $table);
    }

    protected function dieWithErrors($errors)
    {
        print "ERROR:\n";
        print implode("\n", $errors);
        exit;
    }
}

