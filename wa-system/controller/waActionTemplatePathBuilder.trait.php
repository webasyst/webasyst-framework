<?php

trait waActionTemplatePathBuilder
{
    /**
     * Directory inside app or plugin where is templates of action
     * Directory must have trailing slash and must not have leading slash and must not have application of plugin path
     * @return string
     */
    abstract protected function getTemplateDir();

    /**
     * Directory inside app or plugin where is legacy templates of action
     * Directory must have trailing slash and must not have leading slash and must not have application of plugin path
     * @return string
     */
    abstract protected function getLegacyTemplateDir();

    /**
     * Get templates directory variants that depends on which ui version is supported current application
     * This method always must return not empty array
     * Each array item (directory) must have trailing slash and must not have leading slash and must not have application of plugin path
     * @param string $app_id - current application of action
     * @param
     * @return string[]
     * @throws waException
     */
    protected function getTemplateDirVariants($app_id)
    {
        $ui = wa()->whichUI($app_id);
        if ($ui === '2.0') {
            return [$this->getTemplateDir()];
        }
        if (wa()->getEnv() === 'backend') {
            return [$this->getLegacyTemplateDir(), $this->getTemplateDir()];
        }
        return [$this->getTemplateDir()];
    }

    /**
     * Build template path with taking into account dir variants
     * @param waView $view - view instance
     * @param string $app_id - current app id
     * @param string $template - template file path inside templates directory (getTemplateDir(), getLegacyTemplateDir())
     *                              Must not have leading slash
     * @param string $plugin_root - plugin root, if this is about app, skip this parameter of pass empty string
     * @return string
     * @throws waException
     */
    protected function buildTemplatePath(waView $view, $app_id, $template, $plugin_root = '')
    {
        $template_dir_variants = $this->getTemplateDirVariants($app_id);

        // find first existing template in template dir variants
        foreach ($template_dir_variants as $template_dir) {
            $template_path = $plugin_root . $template_dir . $template;
            $postfix = $view->getPostfix();
            $postfix_len = strlen($postfix);
            if (substr($template_path, -$postfix_len, $postfix_len) !== $postfix) {
                $template_path = $plugin_root . $template_dir . $template . $view->getPostfix();
            }
            if ($view->templateExists($template_path)) {
                return $template_path;
            }
        }

        // even if existing template not found we must anyway return something
        $template_dir = reset($template_dir_variants);
        return $plugin_root . $template_dir . $template . $view->getPostfix();
    }
}
