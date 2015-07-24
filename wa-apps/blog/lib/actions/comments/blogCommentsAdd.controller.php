<?php

class blogCommentsAddController extends waJsonController
{
    private $post_id;
    private $parent_id;

    public function execute()
    {
        $this->post_id = max(0, $this->getRequest()->get('id', 0, waRequest::TYPE_INT));
        $this->parent_id = max(0, $this->getRequest()->post('parent', 0, waRequest::TYPE_INT));

        $comment_model = new blogCommentModel();
        $post_model = new blogPostModel();

        /**
         *
         * Parent comment data
         * @var array
         */
        $parent = null;

        $stream = false;

        //find comment parent
        if ($this->parent_id && ($parent = $comment_model->getById($this->parent_id))) {
            if ($this->post_id && ($this->post_id != $parent['post_id'])) {
                throw new waRightsException(_w('Access denied'));
            }
            if (!$this->post_id) {
                $stream = true;
            }
            $this->post_id = $parent['post_id'];
        } else {
            $this->parent_id = 0;
        }

        //find post
        if (!$this->post_id || !($post = $post_model->getBlogPost($this->post_id))) {
            throw new waException(_w('Post not found'), 404);
        };

        $contact_id = $this->getUser()->getId();

        #check rights
        $rights = blogHelper::checkRights($post['blog_id'], $contact_id, blogRightConfig::RIGHT_READ);


        //check comment mode
        if (!$post['comments_allowed']) {
            throw new waException(_w("Isn't allowed comment to this post"));
        }

        $comment = array(
			'blog_id'		 => $post['blog_id'],
			'post_id'		 => $this->post_id,
			'contact_id'	 => $contact_id,
			'text'			 => $this->getRequest()->post('text'),
            'auth_provider'  => blogCommentModel::AUTH_USER,
        );

        $this->errors += $comment_model->validate($comment);
        if (count($this->errors) > 0) {
            return ;
        }

        $id = $comment_model->add($comment, $this->parent_id);
        $this->logAction('comment_add',  $id);


        $comment =  $comment_model->getById($id);
        //$comment['new'] = false;
        $comment['parent'] = $this->parent_id;
        if ($stream) {
            $comment['parent_text'] = $parent?$parent['text']:null;
            $comment['parent_status'] = $parent?$parent['status']:null;
        } else {
            $count = $comment_model->getCount($post['blog_id'],$this->post_id);
            $this->response['count_str'] = $count." "._w('comment', 'comments', $count);
        }

        $comment['rights'] = $rights;

        $comment['post'] = &$post;

        $post['comments'] = $comment_model->prepareView(array($comment), array('photo_url_20'));
        blogHelper::extendRights($post['comments'], array(), $contact_id);
        if ($stream) {
            $posts = array($this->post_id=>&$post);
            $blog_model = new blogBlogModel();
            $extend_data = array(
            	'blog'=>$blog_model->search(array('id'=>$this->post_id))->fetchSearchAll(),
            );
            $post_model->prepareView($posts, array('link'=>true), $extend_data);
        } else {
            unset($comment['post']);
        }


        $view = wa()->getView();
        $view->assign('post', $post);
        $view->assign('contact_rights', $this->getUser()->getRights('contacts', 'backend'));
        $template = $view->fetch('templates/actions/post/include.comments.html');

        $this->getResponse()->addHeader('Content-type', 'application/json');

        $this->response['template'] = $template;
    }
}