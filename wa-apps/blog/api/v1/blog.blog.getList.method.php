<?php

class blogBlogGetListMethod extends waAPIMethod
{
    protected $method = 'GET';
    public function execute()
    {
        $blog_model = new blogBlogModel();
        $this->response = array_values($blog_model->getAvailable(
                wa()->getUser(),
                array(),
                null, 
                array('new'=>true, 'expire'=>1,'link' => false)
        ));
        $this->response['_element'] = 'blog';
    }
}