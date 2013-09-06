<?php

class developerBackendUiAction extends developerAction
{
    public function execute()
    {
        $this->layout->assign('page', 'ui');

        // All icons from wa-1.0.css
        if ( ( $wa_css = @file_get_contents(wa()->getConfig()->getRootPath().'/wa-content/css/wa/wa-1.0.css'))) {
            if (preg_match_all('~^\s*(\.icon1[60]\.[a-z0-9\-]+)\s~um', $wa_css, $m)) {
                $this->view->assign('icons', $m[1]);
            }
        }
    }
}

