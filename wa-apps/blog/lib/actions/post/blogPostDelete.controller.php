<?php
class blogPostDeleteController extends waJsonController
{
    public function execute()
    {
        if ($ids = $this->getRequest()->post('id',null,waRequest::TYPE_ARRAY_INT)) {
            $post_model = new blogPostModel();
            $blog_model = new blogBlogModel();
            $blogs = $blog_model->getAvailable($this->getUser(),'id');
            $options = array('id'=>$ids,'blog_id'=>array_keys($blogs));
            $this->response['deleted'] = $post_model->deleteByField($options);
            $this->logAction('post_delete', implode(',', $ids));
        } else {
            $this->errors[] = 'empty request';
        }
    }
}