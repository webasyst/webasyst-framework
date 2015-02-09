<?php

class blogBlogGetInfoMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $id = $this->get('id', true);
        
        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable(wa()->getUser());
        if (isset($blogs[$id])) {
            $this->response = $blogs[$id];
        } else {
            throw new waAPIException('invalid_param', 'Blog not found', 404);
        }
    }
}
