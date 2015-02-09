<?php

class blogBlogAddMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        if (!wa()->getUser()->getRights('blog', blogRightConfig::RIGHT_ADD_BLOG, true)) {
            throw new waAPIException('access_denied', 403);
        }
        
        $data = waRequest::post();
        
        // check required param name
        $this->post('name', true);
        
        $data = array_merge($data, array(
            'color' => 'b-white',
            'icon' => 'blog',
            'url' => blogHelper::transliterate($data['name'])
        ));
        
        $blog_model = new blogBlogModel();
        $data['sort'] = (int)$blog_model->select('MAX(`sort`)')->fetchField() + 1;
        $blog_id = $blog_model->insert($data);
        wa()->getUser()->setRight('blog', "blog.{$blog_id}", blogRightConfig::RIGHT_FULL);
        
        // return info of the new blog
        $_GET['id'] = $blog_id;
        $method = new blogBlogGetInfoMethod();
        $this->response = $method->getResponse(true);        
        
    }
}