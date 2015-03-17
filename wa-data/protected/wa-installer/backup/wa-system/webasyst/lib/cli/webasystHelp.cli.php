<?php
class webasystHelpcli extends waCliController
{
    public function execute()
    {
        $path = dirname(__FILE__);
        $files = waFiles::listdir($path);
        print "available CLI actions:\n";
        $callback = create_function('$m', 'return strtolower($m[1]);');
        $actions = array();
        foreach ($files as $file) {
            if (preg_match('/^webasyst(\w+)\.cli\.php$/', $file, $matches)) {
                $action = preg_replace_callback('/^([\w]{1})/', $callback, $matches[1]);
                $actions[] = $action;
            }
        }
        asort($actions);
        print implode("\n", $actions);
    }
}
