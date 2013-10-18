<?php

class blogBlogUpdateMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute() 
    {
        $id = $this->get('id', true);
        
        if (!wa()->getUser()->getRights("blog.{$id}", true) < blogRightConfig::RIGHT_FULL) {
            throw new waAPIException('access_denied', 403);
        }

        $blog_model = new blogBlogModel();
        $blog = $blog_model->getById($id);
        if ($blog) {
            $data = waRequest::post();
            $blog_model->updateById($id, $data);
            $method = new blogBlogGetInfoMethod();
            $this->response = $method->getResponse(true);
        } else {
            throw new waAPIException('invalid_param', 'Blog not found', 404);
        }
        
    }
    
}