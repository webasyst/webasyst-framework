<?php
class blogPostMoveController extends waJsonController
{
    public function execute()
    {
        if ($target_blog = max(0,$this->getRequest()->post('blog',0,waRequest::TYPE_INT))) {
            $blog_model = new blogBlogModel();
            if ($blog = $blog_model->getById($target_blog) ) {
                if ($ids = $this->getRequest()->post('id',null,waRequest::TYPE_ARRAY_INT)) {
                    $post_model = new blogPostModel();
                    $comment_model = new blogCommentModel();
                    $this->response['moved'] = array();
                    foreach ($ids as $id) {
                        try {
                            //rights will checked for each record separately
                            $post_model->updateItem($id, array('blog_id'=>$target_blog));
                            $comment_model->updateByField('post_id', $id, array('blog_id'=>$target_blog));
                            $this->response['moved'][$id] = $id;
                        } catch(Exception $ex) {
                            if (!isset($this->response['error'])) {
                                $this->response['error'] = array();
                            }
                            $this->response['error'][$id] = $ex->getMessage();
                        }
                    }

                    $this->response['style'] = $blog['color'];
                    $blog_model->recalculate();
                }
            } else {

            }
        }
    }
}