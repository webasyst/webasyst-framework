<?php

/**
 * Get PHP and Smarty code from POST data, execute and print output to browser.
 */
class developerBackendExecAction extends developerAction
{
    public function execute()
    {
        error_reporting(E_ALL | E_STRICT | E_NOTICE);
        ini_set('display_errors', 'On');
        ini_set('log_errors', 'Off');

        $code = waRequest::post('code');
        $code = preg_replace('@^\s*<\?(php)?\s+@', '', $code);
        eval($code); // Welcome to the dark side!
    }

    protected function isCached(): bool
    {
        return false;
    }

    protected function getTemplate(): string
    {
        $tmpl = waRequest::post('tmpl', '', waRequest::TYPE_STRING_TRIM);
        return 'string:</pre>' . ($tmpl ? '<hr>' . PHP_EOL . '<h3>[`Smarty`]</h3>' . PHP_EOL . $tmpl : '');
    }

    /* allows to use `$this->shop_product_model` in evaled code; note that `wa('shop');` is still required */
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

    public function setLayout(waLayout $layout = null)
    {
        $this->layout = null; // never use layout for this action
    }
}
