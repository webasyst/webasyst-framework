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
            }
            elseif ($post_id = waRequest::param('id', 0, 'int')) {
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
        $available =  blogHelper::getAvailable();

        // it's preview
        $hash = waRequest::get('preview');

        $post = $post_model->search(
        array(
                'url' => $post_slug,
                'status' => $hash ? false : blogPostModel::STATUS_PUBLISHED
        ), array(
                'comments' => $show_comments ? array(50,20) : false
        ), array('blog'=>$available,)
        )->fetchSearchItem();

        if (!$post) {
            throw new waException(_w('Post not found'), 404);
        }

        if ($post['status'] != blogPostModel::STATUS_PUBLISHED) {

            $hash = base64_decode($hash);
            list($hash, $user_id) = array(substr($hash, 0, 32), substr($hash, 32));

            $options = array(
				'contact_id' => $post['contact_id'],
        	    'blog_id' => $post['blog_id'],
        	    'post_id' => $post['id'],
        	    'user_id' => $user_id
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

        if ($this->getRequest()->param('title_type','blog_post') == 'blog_post') {
            if ($title) {
                $this->getResponse()->setTitle($title. " » " . $post['title']);
            } elseif(isset($available[$post['blog_id']]) && ($title = $available[$post['blog_id']]['title'])) {
                $this->getResponse()->setTitle($title . " » " . $post['title']);
            } else {
                $this->getResponse()->setTitle($post['title']);
            }
        } else {
            $this->getResponse()->setTitle($post['title']);
        }

        $blog_id = (array)$this->getRequest()->param('blog_id');
        if (!in_array($post['blog_id'],$blog_id)) {
            if ($this->getRequest()->param('blog_url_type') == 0) {
                if (isset($available[$post['blog_id']])) {
                    $this->redirect($post['link'], 301);
                }
            }
            throw new waException(_w('Post not found'), 404);
        }
        $this->getRequest()->setParam('blog_id',$post['blog_id']);

        $current_user = wa()->getUser();

        if (isset($post['comments']) && !empty($post['comments'])) {

            $depth = 1000;
            foreach ($post['comments'] as $key => $comment) {
                if ($comment['status'] == blogCommentModel::STATUS_DELETED) {
                    if ($comment['depth'] < $depth) {
                        $depth = $comment['depth'];
                    }
                    unset( $post['comments'][$key] );
                    continue;
                }
                if ($comment['depth'] > $depth) {
                    unset( $post['comments'][$key] );
                }
                else {
                    $depth = 1000;
                }
            }
        }

        $errors = array();
        $form = array();

        if ( $storage->read('errors') !== null ) {
            $errors = $storage->read('errors');
            $form = $storage->read('form');
            $storage->remove('errors');
            $storage->remove('form');
        }

        $post['comment_link'] = blogPost::getUrl($post,'comment');
        $post['link'] = blogPost::getUrl($post);


        /**
         * @event frontend_post
         * @param array[string]mixed $post
         * @param array[string]int $post['id']
         * @param array[string]int $post['blog_id']
         * @return array[string][string]string $return[%plugin_id%]
         * @return array[string][string]string $return[%plugin_id%]['footer']
         */
        $this->view->assign('frontend_post', wa()->event('frontend_post',$post));

        $this->view->assign('errors', $errors);
        $this->view->assign('form', $form);
        $this->view->assign('post', $post);
        $this->view->assign('show_comments',$show_comments);

        $current_user['photo20'] = $this->getUser()->getPhoto(20);
        $this->view->assign('current_user', $current_user);

        $this->view->assign('theme', waRequest::param('theme', 'default'));

        $app_url = wa()->getAppStaticUrl();

        $current_auth = false;
        $current_auth_source = false;
        if (isset($_SESSION['auth_user_data'])) {
            $current_auth = $_SESSION['auth_user_data'];
            $current_auth['photo_url_20'] = ($current_auth['source'] == 'guest')?false:"{$app_url}img/{$current_auth['source']}.png";
            $current_auth_source = $_SESSION['auth_user_data']['source'];
        }
        $this->view->assign('current_auth_source', $current_auth_source);
        $this->view->assign('current_auth', $current_auth);

        $auth_adapters = wa()->getAuthAdapters();
        $adapters = array();

        foreach ($auth_adapters as $name => $adapter) {
            $adapters[$name] = array (
				'name' => $adapter->getName(),
				'photo_url_20' => "{$app_url}img/{$name}.png",
				'url'=>wa()->getRouteUrl('blog/frontend/oauth', array('provider'=>$name), true),
            );
        }
        $this->view->assign('auth_adapters', $adapters);
        $this->view->getHelper()->globals($this->getRequest()->param());
    }

    /**
     * protected backend preview
     * @throws waException
     * @return void
     */
    public function backendExecute()
    {
        $post_id = waRequest::param('post_id', false, waRequest::TYPE_INT);
        if ($post_id) {
            $post_model = new blogPostModel();
            $show_comments = $this->appSettings('show_comments', true);

            $photo_sizes = array(50,20);
            $search_options =array('id'=>$post_id,'status'=>false);
            if (!($this->getUser()->isAdmin($this->getApp()))) {
                $search_options['contact_id'] = $this->getUser()->getId();
            }

            $post = $post_model->search($search_options,array('comments'=>false))->fetchSearchItem();

        }

        if (!isset($post)) {
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
            $rights = $user->getRights($this->getApp(),"blog.{$post['blog_id']}");
            return $rights >= blogRightConfig::RIGHT_READ_WRITE;
        }
        return false;
        //     	if ($rights < blogRightConfig::RIGHT_FULL) {
        //     		var_dump($author_id != $post['contact_id'], $blog['status'] != blogBlogModel::STATUS_PUBLIC);
        //     		if ($author_id != $post['contact_id'] || $blog['status'] != blogBlogModel::STATUS_PUBLIC) {
        //     			//restrict access
        //     			return false;
        //     		}
        //     	}
    }
}
