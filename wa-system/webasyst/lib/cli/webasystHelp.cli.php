<?php

class webasystHelpCli extends waCliController
{
    public function execute()
    {
        $actions = array();
        $files = waFiles::listdir(dirname(__FILE__));
        foreach ($files as $file) {
            if (preg_match('/^webasyst(\w+)\.cli\.php$/', $file, $matches) && ($matches[1] != 'Help')) {
                $action = preg_replace_callback('/^([\w]{1})/', array(__CLASS__, 'replace'), $matches[1]);
                $actions[] = $action;
            }
        }

        print "Available CLI actions:\n";
        print implode("\n", $actions)."\n";
    }

    private static function replace($m)
    {
        return strtolower($m[1]);
    }
}
