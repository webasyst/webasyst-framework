<?php

class blogCategoryToolbarPluginBackendAction extends waViewAction
{
    public function execute()
    {
        $category_model = new blogCategoryModel();
        $this->view->assign('categories', $category_model->getByPost($this->params['post_id']));
        $this->view->assign('categories_all', blogCategory::getAll());
    }
}

