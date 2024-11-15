<?php

class siteVariablesAction extends waViewAction {
    /**
     * @throws \waException
     */
    public function execute() {
        $id = waRequest::get('id');
        $is_block_page = waRequest::request('is_block_page', 0, waRequest::TYPE_INT);
        $is_new_variable = waRequest::request('is_new_variable', 0, waRequest::TYPE_INT);

        $model = new siteVariableModel();
        $variables = $model->order('sort')->fetchAll('id');
        $apps = wa()->getApps();

        foreach ($variables as $variable_id => $variable) {
            if (empty($variable['app'])) {
                if (($pos = strpos($variable_id, '.')) !== false) {
                    $app_id = substr($variable_id, 0, $pos);
                    if (isset($apps[$app_id])) {
                        $variables[$variable_id]['app_icon'] = $apps[$app_id]['icon'];
                    }
                }
            }
        }

        if ($id === false) {
            $id = key($variables);
        }
        $this->view->assign('variables', $variables);
        if ($id && isset($variables[$id])) {
            $variable = $variables[$id];
        } else {
            $variable = null;
        }
        $this->view->assign('variable', $variable);
        $this->view->assign('is_block_page', $is_block_page);
        $this->view->assign('is_new_variable', $is_new_variable);
        $this->view->assign('editor', true);

        $this->view->assign('domain_id', siteHelper::getDomainId());
    }
}
