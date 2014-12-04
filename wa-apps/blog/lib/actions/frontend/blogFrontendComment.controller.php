<?php

class blogFrontendCommentController extends waJsonController
{
    /**
     *
     * Blog ID
     * @var int
     */
    private $blog_id;

    /**
     *
     * Post ID
     * @var int
     */
    private $post_id;
    private $post;

    /**
     *
     * Post comment parent ID
     * @var int
     */
    private $parent_id;
    private $comment_id;
    /**
     *
     * @var blogCommentModel
     */
    private $comment_model;

    public function execute()
    {

        if (!$this->appSettings('show_comments',true)) {
            throw new waException(_ws("Page not found"),404);
        }
        $this->comment_model = new blogCommentModel();

        $this->blog_id = waRequest::param('blog_id', false, waRequest::TYPE_ARRAY_INT);

        $this->verify();
        if ($this->getRequest()->method() == 'post') {
            $res = $this->addComment();
        } else {
            $this->comment_id = waRequest::param('blog_id', false, waRequest::TYPE_ARRAY_INT);
            $res = true;
        }

        if (waRequest::get('json')) {
            if($this->comment_id) {
                $this->displayComment();
            }
        } else {
            if (!$res) {
                var_export($this->errors);exit;
                //handle error on non ajax
            }
            $url = blogPost::getUrl($this->post).'#comment'.intval($this->parent_id?$this->parent_id:$this->comment_id);
            $this->redirect($url);
        }
    }

    private function verify()
    {
        $post_slug = waRequest::param('post_url', false, waRequest::TYPE_STRING);

        $post_model = new blogPostModel();
        $this->post = $post_model->getBySlug($post_slug);

        if (!$this->post ||
        $this->post['status'] != blogPostModel::STATUS_PUBLISHED ||
        !$this->post['comments_allowed']) {
            throw new waException(_w('Post not found'), 404);
        }

        if ($this->blog_id && !in_array($this->post['blog_id'], (array)$this->blog_id)) {
            throw new waException(_w('Post not found'), 404);
        }

    }

    private function addComment()
    {

        $comment = array(
            'blog_id'		 => $this->post['blog_id'],
            'post_id'		 => $this->post['id'],
            'contact_id'	 => $this->getUser()->getId(),
            'text'			 => waRequest::post('text'),
        );

        if ($this->getUser()->getId()) {
            $comment['auth_provider'] = 'user';
        } else {
            $comment['auth_provider'] = waRequest::post('auth_provider', 'guest', 'string_trim');
            if ($comment['auth_provider'] == 'user') {
                $comment['auth_provider'] = 'guest';
            } elseif (!$comment['auth_provider']) {
                $comment['auth_provider'] = 'guest';
            }

        }

        switch($adapter = $comment['auth_provider']) {
            case 'user': {
                break;
            }
            case 'guest': {

                $comment['name']		 = waRequest::post('name', '', 'string_trim');
                $comment['email']		 = waRequest::post('email', '', 'string_trim');
                $comment['site']		 = waRequest::post('site', '', 'string_trim');
                $this->getStorage()->del('auth_user_data');
                if ($this->appSettings('require_authorization', false)) {
                    $this->errors[] = array('name' => _w('Only registered users can add comments'));
                    break;
                }
                if ($this->appSettings('request_captcha',true)) {
                    $captcha = new waCaptcha();
                    if(!wa()->getCaptcha()->isValid()) {
                        $this->errors[] = array('captcha' => _w('Invalid captcha code'));
                    }
                }
                break;
            }
            default: {
                $auth_adapters = wa()->getAuthAdapters();
                if (!isset($auth_adapters[$adapter])) {
                    $this->errors[] = _w('Invalid auth provider');
                } elseif ($user_data = $this->getStorage()->get('auth_user_data')) {
                    $comment['name'] = $user_data['name'];
                    $comment['email'] = '';
                    $comment['site'] = $user_data['url'];
                } else {
                    $this->errors[] = _w('Invalid auth provider data');
                }
                break;
            }
        }

        $this->errors += $this->comment_model->validate($comment);

        if (count($this->errors) > 0) {
            if (waRequest::get('json')) {
                $this->getResponse()->addHeader('Content-type', 'application/json');
            }
            return false;
        }

        $this->parent_id = (int)waRequest::post('parent', 0);
        try {
            $comment['post_data'] = $this->post;
            $this->comment_id = $this->comment_model->add($comment, $this->parent_id);
            $this->logAction('comment_add', 'frontend');
            return true;
        }
        catch (Exception $e) {
            throw new waException(_w('Database error'));
        }

    }

    private function displayComment()
    {
        $this->getResponse()->addHeader('Content-type', 'application/json');
        if ($this->comment_id && ($comment = $this->comment_model->getById($this->comment_id) )) {
            $count = $this->comment_model->getCount($comment['blog_id'], $comment['post_id']);
            $comments = $this->comment_model->prepareView(array($comment), array('photo_url_20', 'photo_url_50'),array('user'=>true,'escape'=>true));

            $theme = waRequest::param('theme', 'default');
            $theme_path = wa()->getDataPath('themes', true).'/'.$theme;
            if (!file_exists($theme_path) || !file_exists($theme_path.'/theme.xml')) {
                $theme_path = wa()->getAppPath().'/themes/'.$theme;
            }

            $template = 'file:comment.html';
            $view = wa()->getView(array('template_dir' => $theme_path));
            $view->assign('comment', array_shift($comments));

            $this->response['template']	  = $view->fetch($template);
            $this->response['count_str']  = $count." "._w('comment', 'comments', $count);
            $this->response['parent']	  = $this->parent_id;
            $this->response['comment_id'] = $this->comment_id;
        } else {
            throw new waException(_w('Comment not found'), 404);
        }
    }
}