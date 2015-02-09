<?php 

function smarty_function_wa_snippet($params, &$smarty)
{
    if (isset($params['id']) && $params['id'] && wa()->appExists('site')) {
        wa('site');
        $model = new siteSnippetModel();
        $snippet = $model->getById($params['id']);

        unset($params['id']);

        if ($params) {
            $smarty->assign('params', $params);
        }

        if ($snippet) {
            $cache_id = isset($smarty->getParent()->tpl_vars['cache_id']) ? $smarty->getParent()->tpl_vars['cache_id'] : null;
            if ($cache_id && isset($cache_id->value)) {
                $cache_id = $cache_id->value;
            }
            return $smarty->fetch('string:'.$snippet['content'], $cache_id ? $cache_id : null);
        }
    }
    return '';
}
