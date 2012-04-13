<?php

class blogFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->setThemeTemplate('index.html');
        $this->getResponse()->addJs("js/jquery.pageless2.js?v=".wa()->getVersion(),true);
        $this->view->assign('site_theme_url', wa()->getDataUrl('themes',true,'site').'/'.waRequest::param('theme', 'default').'/');
        $this->view->assign('action',$action = waRequest::param('action','default'));
        waRequest::setParam('action',$action);
        $params = waRequest::param();
        /**
         * @event frontend_action_default
         * @event frontend_action_post
         * @event frontend_action_page
         * @event frontend_action_error
         * @param array[string]mixed $params request params
         * @return array[string][string]string $return['%plugin_id%']
         * @return array[string][string]string $return['%plugin_id%'][nav_before]
         * @return array[string][string]string $return['%plugin_id%'][footer]
         * @return array[string][string]string $return['%plugin_id%'][head]
         */
        $this->view->assign('settlement_one_blog', isset($params['blog_id']) && ($params['blog_url_type'] == $params['blog_id']));
        $this->view->assign('frontend_action',wa()->event('frontend_action_'.$action,$params));
    }
}