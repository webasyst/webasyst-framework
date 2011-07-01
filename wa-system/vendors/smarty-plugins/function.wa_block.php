<?php 

function smarty_function_wa_block($params, &$smarty)
{
	if (isset($params['id']) && $params['id'] && wa()->appExists('site')) {
		wa('site');
		$block_model = new siteBlockModel();
		$block = $block_model->getById($params['id']);
		
		unset($params['id']);
		
		if ($params) {
			$smarty->assign('params', $params);
		}
		
		if ($block) {
			$cache_id = isset($smarty->getParent()->tpl_vars['cache_id']) ? $smarty->getParent()->tpl_vars['cache_id'] : null;
			if ($cache_id && isset($cache_id->value)) {
				$cache_id = $cache_id->value;
			}			
			return $smarty->fetch('string:'.$block['content'], $cache_id ? $cache_id : null);
		}
	}
	return '';
}
