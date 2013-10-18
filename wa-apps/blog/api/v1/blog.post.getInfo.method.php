<?php

class blogPostGetInfoMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $id = $this->get('id', true);
        
        $post_model = new blogPostModel();
        $post = $post_model->search(array('id' => $id))->fetchSearchItem();
        if ($post) {
            $blog_model = new blogBlogModel();
            $blog = $blog_model->getById($post['blog_id']);
            if ( ($blog['status'] != blogBlogModel::STATUS_PUBLIC) || ($post['status'] != blogPostModel::STATUS_PUBLISHED) ) {
                blogHelper::checkRights($post['blog_id'],true,blogRightConfig::RIGHT_READ);
            }
            $this->response = $post;
        } else {
            throw new waAPIException('invalid_param', 'Post not found', 404);
        }
    }
}
