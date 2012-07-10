<?php
/**
 * @author WebAsyst Team
 *
 */
class blogCommentsAction extends waViewAction
{
    public function execute()
    {
        $contact_photo_size = 20;

        $comments_per_page = max(1, intval($this->getConfig()->getOption('comments_per_page')));
        $page = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));

        $blog_models = new blogBlogModel();
        $user = $this->getUser();
        $blogs = blogHelper::getAvailable();

        $comment_model = new blogCommentModel();

        $offset = $comments_per_page * ($page - 1);
        $prepare_options = array('datetime' => blogActivity::getUserActivity());
        $fields = array("photo_url_{$contact_photo_size}");
        $blog_ids = array_keys($blogs);

        $comments = $comment_model->getList($offset, $comments_per_page, $blog_ids, $fields, $prepare_options);
        $comments_all_count = $comment_model->getCount($blog_ids, null, null, null, null, null);

        $post_ids = array();
        foreach ($comments as $comment) {
            $post_ids[$comment['post_id']] = true;
        }

        //get related posts info
        $post_model = new blogPostModel();
        $search_options = array('id'=> array_keys($post_ids));
        $extend_options = array('user'=>false, 'link'=>true, 'rights'=>true, 'plugin'=>false, 'comments'=>false);
        $extend_data = array('blog'=>$blogs);
        $posts = $post_model->search($search_options, $extend_options, $extend_data)->fetchSearchAll(false);

        $comments = blogCommentModel::extendRights($comments, $posts);

        $comments_count = ($page - 1) * $comments_per_page + count($comments);

        if ($page == 1) {

            $this->setLayout(new blogDefaultLayout());
            $this->getResponse()->setTitle(_w('Comments'));
        }

        /**
         * Backend comments view page
         * UI hook allow extends backend comments view page
         * @event backend_comments
         * @param array[int][string]mixed $comments
         * @param array[int][string]int $comments[%id%][id] comment id
         * @return array[string][string]string $return[%plugin_id%]['toolbar'] Comment's toolbar html
         */
        $this->view->assign('backend_comments', wa()->event('backend_comments', $comments));

        $this->view->assign('comments', $comments);

        $this->view->assign('comments_count', $comments_count);
        $this->view->assign('comments_total_count', $comments_all_count);
        $this->view->assign('comments_per_page', $comments_per_page);
        $this->view->assign('pages', ceil($comments_all_count / $comments_per_page ));
        $this->view->assign('page', $page);

        $this->view->assign('contact_rights', $this->getUser()->getRights('contacts', 'backend'));

        $this->view->assign('current_contact_id', $user->getId());
        $this->view->assign('current_contact', array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'photo20' => $user->getPhoto($contact_photo_size),
        ));
    }
}