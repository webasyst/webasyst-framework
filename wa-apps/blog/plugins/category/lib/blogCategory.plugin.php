<?php

class blogCategoryPlugin extends blogPlugin
{
    public function postUpdate($post)
    {
        if (isset($post['plugin'])) {
            $categories = isset($post['plugin'][$this->id]) ? array_keys($post['plugin'][$this->id]) : array();
            $category_model = new blogCategoryPostModel();
            $category_model->changePost($post['id'], $categories);
        }
    }

    public function postSearch($options)
    {
        $result = null;
        if (!empty($options['plugin'])) {
            if (!empty($options['plugin'][$this->id])) {
                $result = array();
                $result['join'] = array();
                $category_model = new blogCategoryModel();
                if ($category = $category_model->getByField('url',$options['plugin'][$this->id],'id')) {
                    $result['join'] = array();

                    $result['join']['blog_post_category'] = array(
                    	'condition'=>'blog_post_category.post_id = blog_post.id',
                    );

                    $result['where'] = array('blog_post_category.category_id IN ('.implode(', ',array_keys($category)).')');
                } else {
                    $category = array();
                    $result['where'] = 'FALSE';
                }
                $title = array();
                if ($category) {
                    foreach ($category as $item) {
                        $title[] = $item['name'];
                    }
                } else {
                    $title[] = _wp('not found');
                }
                wa()->getResponse()->setTitle(implode(', ', $title));
            }
        }
        return $result;
    }

    public function backendSidebar($param)
    {
        $output = array();
        $action = new blogCategoryPluginBackendSidebarAction();
        $output['section'] = $action->display();
        return $output;
    }

    public function backendPostEdit($post)
    {
        $output = array();
        $action = new blogCategoryToolbarPluginBackendAction(array('post_id'=>$post['id']));
        $action->setTemplate('plugins/category/templates/actions/backend/ToolbarBackend.html');
        $output['sidebar'] = $action->display(false);
        return $output;
    }



    public function frontendSidebar($params)
    {
        $output = array();
        $category_id = isset($params['category'])?$params['category']:false;

        if($categories = blogCategory::getAll()){
            $output['sidebar'] = '<ul class="menu-v categories">';
            $wa = wa();
            foreach ($categories as $category) {
                blogHelper::extendIcon($category);
                $category['link'] = $wa->getRouteUrl('blog/frontend', array('category'=>urlencode($category['url'])), true);
                $category['name'] = htmlentities($category['name'],ENT_QUOTES,'utf-8');
                $selected = ($category_id && ($category_id == $category['url'])) ? ' class="selected"':'';
                $output['sidebar'] .= <<<HTML
<li{$selected}>
<a href="{$category['link']}" title="{$category['name']}">{$category['name']}</a>
</li>
HTML;
            }
            $output['sidebar'] .= '</ul>';
        }
        return $output;
    }
}