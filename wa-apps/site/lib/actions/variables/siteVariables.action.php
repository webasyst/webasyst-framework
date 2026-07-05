<?php

class siteVariablesAction extends waViewAction {
    /**
     * @throws \waException
     */
    public function execute() {
        $variable_id = waRequest::get('variable_id');
        $block_id = waRequest::get('id');
        $is_block_page = waRequest::request('is_block_page', 0, waRequest::TYPE_INT);
        $is_dialog = (bool)waRequest::request('is_dialog', false, waRequest::TYPE_STRING);

        $model = new siteVariableModel();
        $variables = $model->order('sort')->fetchAll('id');
        $apps = wa()->getApps();

        $this->view->assign('mode', (isset($variable_id) || !isset($block_id) ? 'variables' : 'blocks'));

        foreach ($variables as $v_id => $variable) {
            if (empty($variable['app']) && ($pos = strpos($v_id, '.')) !== false) {
                $app_id = substr($v_id, 0, $pos);
                if (isset($apps[$app_id])) {
                    $variables[$v_id]['app_icon'] = $apps[$app_id]['icon'];
                }
            }
        }

        if (!isset($variable_id)) {
            $variable_id = key($variables);
        }

        if ($variable_id && isset($variables[$variable_id])) {
            $variable = $variables[$variable_id];
        } else {
            $variable = null;
        }

        $domain_id = siteHelper::getDomainId();
        $this->setTemplate('templates/actions/variables/VariablesDialog.html');

        $this->view->assign([
            'variables' => $variables,
            'variable' => $variable,
            'is_block_page' => $is_block_page,
            'is_new_variable' => !$variable_id,
            'editor' => true,
            'domain_id' => $domain_id,
        ]);

        if (!$is_dialog) {
            $domains = siteHelper::getDomains(true);
            $this->setTemplate('templates/actions/variables/Variables.html');
            $this->setLayout(new siteBackendLayout());
            $this->view->assign('domain', $domains[$domain_id]);
            $this->view->assign('domain_idn', waIdna::dec(siteHelper::getDomain()));
        }

        (new siteBlocksAction())->execute();
    }
}
