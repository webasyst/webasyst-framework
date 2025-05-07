<?php

/**
 * Get PHP and smarty code from POST, execute and echo output to browser.
 * Sounds crazy, isn't it?
 */
class developerBackendExecAction extends waViewAction
{
    public function execute()
    {
        // Access control
        $message = '';
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            $message = _w('This application is available for Webasyst admin users only.');
        }
        if (!defined('DEVELOPER_APP_IN_NONDEBUG') && !waSystemConfig::isDebug()) {
            $message = _w('This application works only when developer mode is enabled in Settings app.');
        }
        if ($message) {
            throw new waRightsException($message);
        }

        error_reporting(E_ALL | E_STRICT | E_NOTICE);
        ini_set('display_errors', 1);

        $code = waRequest::post('code');
        $code = preg_replace('@^\s*<\?(php)?\s+@', '', $code);
        eval($code); // Welcome to the dark side!
    }

    protected function isCached()
    {
        return false;
    }

    protected function getTemplate()
    {
        $tmpl = trim(waRequest::post('tmpl'));
        return 'string:</pre>'.($tmpl ? '<h2>[`Smarty`]</h2>'.$tmpl : '');
    }

    public function __get($param)
    {
        static $models = array();
        $type = null;
        $value = null;
        if (preg_match('@^(.+)_model@', $param, $matches)) {
            if (!isset($models[$param])) {
                $classes = array();
                $class = preg_replace_callback('@_([\w])@', array($this, 'camelCase'), $param);
                $classes[] = $class;
                $classes[] = $this->getAppId().ucfirst($class);

                foreach ($classes as $class) {
                    if (class_exists($class)) {
                        $models[$param] = new $class();
                    }
                }
                if (!isset($models[$param])) {
                    throw new waException(sprintf('Model not found for %s (tried %s)', $param, implode(', ', $classes)));
                }
            }
            $value = &$models[$param];
        }

        return $value;
    }

    private function camelCase($matches)
    {
        return ucfirst($matches[1]);
    }

}
