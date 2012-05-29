<?php

class blogCategoryPluginBackendSidebarAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('categories', blogCategory::getAll());
        $this->view->assign('category_url', waRequest::get('category'));
    }
}

