<?php

class uiComponentAction extends uiBackendViewAction
{
    public function execute()
    {
        $component = waRequest::param('essence');
        $component = str_replace("-", "_", $component);

        $template_path = wa()->getAppPath("templates/actions/component/{$component}.html", 'ui');

        if (!file_exists($template_path)) {
            $template_path = wa()->getAppPath("templates/actions/component/home.html", 'ui');
        }

        $this->view->assign(array(
            'component' => $component
        ));

        $this->setTemplate($template_path);
    }
}