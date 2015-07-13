<?php
/**
 * @author Webasyst
 *
 */
class blogBlogDeleteController extends waController
{
    public function execute()
    {
        if ($blog_id = (int) waRequest::post('id')) {
            blogHelper::checkRights($blog_id, true, blogRightConfig::RIGHT_FULL);
            $remove =  waRequest::post('remove');
            if ($remove == 'move') {
                $move_blog_id =  waRequest::post('blog_id');

                blogHelper::checkRights($move_blog_id, true, blogRightConfig::RIGHT_FULL);
                if ($move_blog_id != $blog_id) {
                    blogPost::move($blog_id, $move_blog_id);
                } else {
                    $this->redirect('?module=blog&action=settings&id='.$blog_id);
                }
            }
            $blog_model = new blogBlogModel();
            $blog_model->deleteById($blog_id);
            $this->logAction('blog_delete');
            $this->redirect(wa()->getAppUrl());
        } else {
            $this->redirect(wa()->getAppUrl());
        }
    }
}