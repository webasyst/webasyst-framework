<?php

/**
 * Get rendered block content.
 * URL for AJAX query: {$wa->getUrl('site/frontend/block', ['id' => 'block_name'])}
 */
class siteFrontendBlockAction extends waAction
{
    public function execute()
    {
        $id = waRequest::param('id', '', waRequest::TYPE_STRING);
        
        $blockModel = new siteBlockModel;
        $block = $blockModel->getById($id);
        
        if (!$block && strpos($id, '.') !== false) {
            list($appId, $id) = explode('.', $id);
            $path = wa()->getConfig()->getAppsPath($appId, 'lib/config/site.php');
            if (file_exists($path)) {
                $siteConfig = include($path);
                if (isset($siteConfig['blocks'][$id])) {
                    if (!is_array($siteConfig['blocks'][$id])) {
                        $block = array('content' => $siteConfig['blocks'][$id]);
                    } else {
                        $block = $siteConfig['blocks'][$id];
                    }
                }
            }
        }
        
        if (!$block) {
            throw new waException('Block '.htmlspecialchars($id).' not exists.', 404);
        }
        
        if (empty($block['content'])) {
            return '';
        }
        
        $view = wa()->getView();
        $view->assign(waRequest::get());
        return $view->fetch('string:'.$block['content']);
    }
}
