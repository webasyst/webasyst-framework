<?php

/**
 * Documentation of old UI components.
 */
class developerBackendUiAction extends developerAction
{
    public function execute()
    {
        $this->layout->assign('page', 'ui');
        $icons = [];
        $config = $this->getConfig();
        // All icons from 'wa-1.3.css'
        $css = file_get_contents($config->getPath('content') . '/css/wa/wa-1.3.css');
        if (preg_match_all('~^\s*(\.icon\d{2}\.[-a-z\d]+)~um', $css, $matches)) {
            $icons = $matches[1];
        }
        if (wa()->appExists('shop')) {
            // All icons from 'icons.shop.css'
            $css = file_get_contents($config->getAppPath('css/icons.shop.css'));
            if (preg_match_all('~^\s*(\.icon\d{2}\.ss(\.pt)?\.[-a-z\d]+)~um', $css, $matches)) {
                $icons = array_merge($icons, $matches[1]);
            }
        }
        $this->view->assign('icons', str_replace('.', ' ', $icons));
    }
}
