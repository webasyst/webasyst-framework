<?php
class blogPostAction extends waViewAction
{
    public function execute()
    {
        $post_id = max(0, waRequest::get('id', 0, waRequest::TYPE_INT));
        if (!$post_id) {
            throw new waException(_w('Post not found'), 404);
        }

        $post_model = new blogPostModel();
        $search_options = array('id' => $post_id);
        $extend_options = array('comments' => array(20), 'user' => array('photo_url_50'), 'status' => 'view');

        $post = $post_model->search($search_options, $extend_options)->fetchSearchItem();

        if (!$post) {
            throw new waException(_w('Post not found'), 404);
        }

        $post['rights'] = $this->getRights("blog.{$post['blog_id']}");
        $posts = array(&$post);
        blogHelper::extendRights($posts, array(), $this->getUser()->getId());

        if (isset($post['comments']) && $post['comments']) {
            $post['comments'] = blogCommentModel::extendRights($post['comments'], array($post_id => $post));
        }

        $blog_model = new blogBlogModel();
        $blog = $blog_model->getById($post['blog_id']);

        if (($blog['status'] != blogBlogModel::STATUS_PUBLIC) || ($post['status'] != blogPostModel::STATUS_PUBLISHED)) {
            blogHelper::checkRights($post['blog_id'], true, blogRightConfig::RIGHT_READ);
        }
        $items = $blog_model->prepareView(array($blog));
        $blog = array_shift($items);

        $this->setLayout(new blogDefaultLayout());
        $this->getResponse()->setTitle($post['title']);

        /**
         * Backend post view page
         * UI hook allow extends post view page
         * @event backend_post
         * @param array[string]mixed $post Current page post item data
         * @param array[string]int $post['id'] Post ID
         * @param array[string]int $post['blog_id'] Post blog ID
         * @return array[string][string]string $backend_post['%plugin_id%']['footer'] Plugin %plugin_id% footer html
         */
        $this->view->assign('backend_post', wa()->event('backend_post', $post, array('footer')));

        $user = $this->getUser();
        $this->view->assign('current_contact', array(
            'id'      => $user->getId(),
            'name'    => $user->getName(),
            'photo20' => $user->getPhoto(20),
        ));

        $this->view->assign('blog_id', $blog['id']);
        $this->view->assign('blog', $blog);
        $this->view->assign('contact_rights', $this->getUser()->getRights('contacts', 'backend'));
        if ($this->getConfig()->getOption('can_use_smarty')) {
            try {
                $post['text'] = $this->view->fetch("string:{$post['text']}", $this->cache_id);
            } catch (SmartyException $ex) {
                $post['text'] = blogPost::handleTemplateException($ex, $post);
            }
        }
        $this->view->assign('post', $post);
    }
}