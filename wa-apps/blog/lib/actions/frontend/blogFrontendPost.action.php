<?php

class blogFrontendPostAction extends blogViewAction
{
    protected function isFrontend()
    {
        return wa()->getEnv() == 'frontend';
    }

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setThemeTemplate('post.html', waRequest::param('theme', 'default'));
        /**
         * @todo enable partial caching wait for Smarty 3.2.x
         * @link http://www.smarty.net/forums/viewtopic.php?p=75251
         */
        if (false && ($cache_time = $this->getConfig()->getOption('cache_time'))) {
            if ($post_url = waRequest::param('url', false, 'string_trim')) {
                $this->cache_id = 'url_'.$post_url;
            } elseif ($post_id = waRequest::param('id', 0, 'int')) {
                $this->cache_id = 'id_'.$post_id;
            }
            $this->cache_time = $cache_time;
        }
    }

    public function frontendExecute()
    {
        $post_slug = waRequest::param('post_url', false, waRequest::TYPE_STRING_TRIM);

        $storage = wa()->getStorage();
        $post_model = new blogPostModel();
        $show_comments = $this->appSettings('show_comments', true);
        $request_captcha = $show_comments && $this->appSettings('request_captcha', true);
        $require_authorization = $show_comments && $this->appSettings('require_authorization', false);
        $available = blogHelper::getAvailable();

        // it's preview
        $hash = waRequest::get('preview');

        $post = $post_model->search(
                    array(
                        'url'    => $post_slug,
                        'status' => $hash ? false : blogPostModel::STATUS_PUBLISHED
                    ), array(
                        'comments' => $show_comments ? array(50, 20) : false,
                        'params'   => true,
                        'escape'   => true,
                    ), array('blog' => $available,)
                )->fetchSearchItem();

        if (!$post) {
            throw new waException(_w('Post not found'), 404);
        }

        if ($post['status'] != blogPostModel::STATUS_PUBLISHED) {

            $hash = base64_decode($hash);
            list($hash, $user_id) = array(substr($hash, 0, 32), substr($hash, 32));

            $options = array(
                'contact_id' => $post['contact_id'],
                'blog_id'    => $post['blog_id'],
                'post_id'    => $post['id'],
                'user_id'    => $user_id
            );

            $preview_cached_options = $storage->read('preview');
            $preview_cached_post_options = isset($preview_cached_options['post_id']) ? $preview_cached_options['post_id'] : null;

            if ($preview_cached_post_options && $preview_cached_post_options != $options) {
                $preview_cached_post_options = null;
            }

            if (!$preview_cached_post_options) {
                if ($hash == blogPostModel::getPreviewHash($options, false, false)) {
                    $preview_cached_options['post_id'] = $preview_cached_post_options = $options;
                    $storage->write('preview', $preview_cached_options);
                }
            }
            if (!$preview_cached_post_options) {
                throw new waException(_w('Post not found'), 404);
            }
            if (!$this->checkAuthorRightsToBlog($user_id, $post)) {
                throw new waException(_w('Post not found'), 404);
            }
        }


        $title = $this->getResponse()->getTitle();
        $post_title = htmlspecialchars_decode($post['title'], ENT_QUOTES);
        if ($this->getRequest()->param('title_type', 'blog_post') == 'blog_post') {
            if ($title) {
                $this->getResponse()->setTitle($title." » ".$post_title);
            } elseif (isset($available[$post['blog_id']]) && ($title = $available[$post['blog_id']]['title'])) {
                $this->getResponse()->setTitle($title." » ".$post_title);
            } else {
                $this->getResponse()->setTitle($post_title);
            }
        } else {
            $this->getResponse()->setTitle($post_title);
        }

        // meta title override default title
        if ($post['meta_title']) {
            $this->getResponse()->setTitle($post['meta_title']);
        }

        $this->getResponse()->setMeta('keywords', $post['meta_keywords']);
        $this->getResponse()->setMeta('description', $post['meta_description']);

        $blog_id = (array)$this->getRequest()->param('blog_id');
        if (!in_array($post['blog_id'], $blog_id)) {
            if ($this->getRequest()->param('blog_url_type') == 0) {
                if (isset($available[$post['blog_id']])) {
                    $this->redirect($post['link'], 301);
                }
            }
            throw new waException(_w('Post not found'), 404);
        }
        $this->getRequest()->setParam('blog_id', $post['blog_id']);

        if (isset($post['comments']) && !empty($post['comments'])) {

            $depth = 1000;
            foreach ($post['comments'] as $key => $comment) {
                if ($comment['status'] == blogCommentModel::STATUS_DELETED) {
                    if ($comment['depth'] < $depth) {
                        $depth = $comment['depth'];
                    }
                    unset($post['comments'][$key]);
                    continue;
                }
                if ($comment['depth'] > $depth) {
                    unset($post['comments'][$key]);
                } else {
                    $depth = 1000;
                }
            }
        }

        $errors = array();
        $form = array();

        if ($storage->read('errors') !== null) {
            $errors = $storage->read('errors');
            $form = $storage->read('form');
            $storage->remove('errors');
            $storage->remove('form');
        }

        $post['comment_link'] = blogPost::getUrl($post, 'comment');
        $post['link'] = blogPost::getUrl($post);

        $posts = array(&$post);
        blogPhotosBridge::loadAlbums($posts);

        /**
         * Frontend post view page
         * UI hook allow extends frontend post view page
         * @event frontend_post
         * @param array[string]mixed $post
         * @param array[string]int $post['id']
         * @param array[string]int $post['blog_id']
         * @return array[string][string]string $return[%plugin_id%]['footer']
         */
        $this->view->assign('frontend_post', wa()->event('frontend_post', $post, array('footer')));

        $this->view->assign('errors', $errors);
        $this->view->assign('form', $form);
        $this->view->assign('show_comments', $show_comments);
        $this->view->assign('request_captcha', $request_captcha);
        $this->view->assign('require_authorization', $require_authorization);

        $this->view->assign('theme', waRequest::param('theme', 'default'));

        $storage = wa()->getStorage();
        $current_auth = $storage->read('auth_user_data');
        $current_auth_source = $current_auth ? $current_auth['source'] : null;

        $this->view->assign('current_auth_source', $current_auth_source);
        $this->view->assign('current_auth', $current_auth, true);

        $adapters = wa()->getAuthAdapters();
        $this->view->assign('auth_adapters', $adapters);
        $this->view->getHelper()->globals($this->getRequest()->param());

        if ($this->getConfig()->getOption('can_use_smarty')) {
            try {
                $post['text'] = $this->view->fetch("string:{$post['text']}", $this->cache_id);
            } catch (SmartyException $ex) {
                $post['text'] = blogPost::handleTemplateException($ex, $post);
            }
        }

        $this->view->assign('post', $post);
    }

    /**
     * protected backend preview
     * @throws waException
     * @return void
     */
    public function backendExecute()
    {
        if ($post_id = waRequest::param('post_id', false, waRequest::TYPE_INT)) {
            $search_options = array('id' => $post_id, 'status' => false);
            if (!($this->getUser()->isAdmin($this->getApp()))) {
                $search_options['contact_id'] = $this->getUser()->getId();
            }

            $post_model = new blogPostModel();
            $post = $post_model->search($search_options, array('comments' => false))->fetchSearchItem();
        }

        if (empty($post)) {
            throw new waException(_w('Post not found'), 404);
        }

        $this->view->assign('post', $post);
    }

    public function execute()
    {
        if ($this->isFrontend()) {
            $this->setLayout(new blogFrontendLayout());
            $this->frontendExecute();
        } else {
            $this->backendExecute();
        }
    }

    private function checkAuthorRightsToBlog($author_id, $post)
    {
        $user = new waUser($author_id);
        if ($user->getId()) {
            $rights = $user->getRights($this->getApp(), "blog.{$post['blog_id']}");
            return $rights >= blogRightConfig::RIGHT_READ_WRITE;
        }
        return false;
    }
}
