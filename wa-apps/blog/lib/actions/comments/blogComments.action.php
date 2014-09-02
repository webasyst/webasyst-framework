<?php
/**
 * @author Webasyst
 *
 */
class blogCommentsAction extends waViewAction
{
    public function execute()
    {
        
        $user = $this->getUser();
        
        $filter = waRequest::get('filter');
        $contact_settings = new waContactSettingsModel();
        if (!$filter) {
            $filter = $contact_settings->getOne($user->getId(), $this->getAppId(), 'comments_filter');
            if (!$filter) {
                $filter = 'myposts';
            }
        } else {
            $contact_settings->set($user->getId(), $this->getAppId(), 'comments_filter', $filter);
        }
        
        $contact_photo_size = 20;

        $comments_per_page = max(1, intval($this->getConfig()->getOption('comments_per_page')));
        $page = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));

        $blogs = blogHelper::getAvailable();

        $offset = $comments_per_page * ($page - 1);
        $prepare_options = array('datetime' => blogActivity::getUserActivity());
        $fields = array("photo_url_{$contact_photo_size}");
        $blog_ids = array_keys($blogs);

        $data = $this->getComments(array(
            'offset' => $offset, 
            'comments_per_page' => $comments_per_page, 
            'blog_id' => $blog_ids,
            'filter' => $filter
        ), $fields, $prepare_options);
        $comments = $data['comments'];
        $comments_all_count = $data['comments_all_count'];
        
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
        $this->view->assign('backend_comments', wa()->event('backend_comments', $comments, array('toolbar')));

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
        $this->view->assign('filter', $filter);
        

        $yet_authors_exist = false;
        if ($blogs) {
            $yet_authors_exist = !!($post_model->select('contact_id')->where(
                'blog_id IN ('.implode(',', $blog_ids).') AND contact_id != '.$user->getId()
            )->limit(1)->fetchField());
        }
        $this->view->assign('blogs', $blogs);
        $this->view->assign('yet_authors_exist', $yet_authors_exist);
        
    }
    
    public function getComments($search_options, $fields, $prepare_options) {
        $comment_model = new blogCommentModel();
        
        $post_ids = null;
        $blog_ids = $search_options['blog_id'];
        if (is_numeric($search_options['filter'])) {
            $k = array_search((int) $search_options['filter'], $blog_ids);
            if ($k !== false) {
                $blog_ids = $blog_ids[$k];
            } else {
                $blog_ids = array();
            }
            $search_options['blog_id'] = $blog_ids;
        } else if ($search_options['filter'] == 'myposts') {
            $post_model = new blogPostModel();
            $post_ids = array_keys($post_model->select('id')->where('contact_id='.$this->getUser()->getId())->fetchAll('id'));
            $search_options['post_id'] = $post_ids;
        }
        $counts = (array) $comment_model->getCount(
                    $blog_ids, 
                    $post_ids, 
                    null, null, null, null
                );
        return array(
            'comments' => $comment_model->getList($search_options, $fields, $prepare_options),
            'comments_all_count' => array_sum($counts)
        );
    }
}