<?php 

/**
 * @param $params
 * @param Smarty $smarty
 * @return string
 */
function smarty_function_wa_block($params, &$smarty)
{
    if (isset($params['id']) && $params['id'] && wa()->appExists('site')) {
        wa('site');
        $model = new siteBlockModel();
        $block = $model->getById($params['id']);

        unset($params['id']);

        if ($params) {
            $smarty->assign('params', $params);
        }

        if ($block) {
            return $smarty->fetch('string:'.$block['content']);
        }
    }
    return '';
}
