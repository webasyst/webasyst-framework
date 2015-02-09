<?php
/**
 * @author Webasyst
 *
 */
class blogBlogSortController extends waJsonController
{
    public function execute()
    {
        if ( !wa()->getUser()->isAdmin($this->getApp()) ) {
            $this->errors[] = _w('Access denied');
            return;
        }

        $blog_id = (int) waRequest::get('blog_id');
        $sort = (int) waRequest::get('sort');
        $blog_model = new blogBlogModel();
        $blog_model->sort($blog_id, $sort);
        $this->response = "ok";
        $this->getResponse()->addHeader('Content-type', 'application/json');
    }
}