<?php

class blogPostEditAction extends waViewAction
{

    private $unsaved_post = array();

    private $validate_messages = array();

    public function execute()
    {
        $post_id = waRequest::get('id', null, waRequest::TYPE_INT);

        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable();
        if (!$blogs) {
            $this->setTemplate('BlogNotFound');
            return;
        }
        $blogs = $blog_model->prepareView($blogs);

        if ($post_id) {	// edit post

            $post_model = new blogPostModel();
            $post = $post_model->getById($post_id);
            if (!$post) {
                throw new waException(_w('Post not found'), 404);
            }

            //check rights
            if (blogHelper::checkRights($post['blog_id']) < blogRightConfig::RIGHT_FULL &&
            $post['contact_id'] != $this->getUser()->getId())
            {
                throw new waRightsException(_w('Access denied'));
            }

            $post['datetime'] = $post['datetime'] >= 1971 ? $post['datetime'] : '';

            $blog_id = $post['blog_id'];
            $blog = $blogs[$blog_id];

            $title = trim(sprintf(_w('Editing post %s'), $post['title']));

        } else { // add post

            $date = waRequest::get('date', '');

            $blog = $this->getAllowedBlog($blogs, wa()->getStorage()->read('blog_last_id'));
            if (!$blog) {
                throw new waRightsException(_w('Access denied'));
            }
            $blog_id = $blog['id'];

            $post = array(
				'title' => $this->getRequest()->post('title','',waRequest::TYPE_STRING_TRIM),
				'text' => $this->getRequest()->post('text','',waRequest::TYPE_STRING_TRIM),
				'continued_text' => null,
				'categories' => array(),
				'contact_id' => wa()->getUser()->getId(),
				'url' => '',
				'blog_id' => $blog_id,
				'comments_allowed' => true,
            );

            $post['id'] = '';
            $post['status'] = $date ? blogPostModel::STATUS_DEADLINE : blogPostModel::STATUS_DRAFT;
            $post['datetime'] = $date ? waDateTime::format('date', $date) : '';

            $title = _w('Adding new post');

        }

        $all_links = blogPostModel::getPureUrls($post);
        $post['other_links'] = $all_links;
        $post['link'] = array_shift($post['other_links']);

        $post['remaining_time'] = null;
        if ($post['status'] == blogPostModel::STATUS_SCHEDULED && $post['datetime']) {
            $post['remaining_time'] = $this->calculateRemainingTime($post['datetime']);
        }


        if ($blog['rights'] >= blogRightConfig::RIGHT_FULL) {
            $users = blogHelper::getAuthors($post['blog_id']);
        } else {
            $user = $this->getUser();
            $users = array($user->getId()=>$user->getName());
        }
        // preview hash for all type of drafts
        if ($post['status'] != blogPostModel::STATUS_PUBLISHED) {
            $options = array(
				'contact_id' => $post['contact_id'],
				'blog_id' => $blog_id,
				'post_id' => $post['id'],
				'user_id' => wa()->getUser()->getId()
            );
            $preview_hash = blogPostModel::getPreviewHash($options);
            $this->view->assign('preview_hash', base64_encode($preview_hash.$options['user_id']));
        }

        $this->view->assign('no_settlements', empty($all_links) ? true : false);
        $this->view->assign('params', $this->getPostParams($post['id']));
        $this->view->assign('blog', $blog);
        $this->view->assign('users', $users);
        $this->view->assign('blogs', $blogs);

        $allow_change_blog = 0;
        foreach ($blogs as $blog_item) {
            if ($blog_item['rights'] >= blogRightConfig::RIGHT_READ_WRITE) {
                ++$allow_change_blog;
            }
        }

        $this->view->assign('allow_change_blog', $allow_change_blog);
        $this->view->assign('post_id', $post_id);
        $this->view->assign('datetime_timezone', waDateTime::date("T", null, wa()->getUser()->getTimezone()));

        /**
         * Backend post edit page
         * UI hook allow extends post edit page
         * @event backend_post_edit
         * @param array[string]mixed $post
         * @param array[string]int $post['id']
         * @param  array[string]int $post['blog_id']
         * @return array[string][string]string $return[%plugin_id%]['sidebar'] Plugin sidebar html output
         * @return array[string][string]string $return[%plugin_id%]['toolbar'] Plugin toolbar html output
         */
        $this->view->assign('backend_post_edit', wa()->event('backend_post_edit', $post));

        $app_settings = new waAppSettingsModel();
        $show_comments = $app_settings->get($this->getApp(), 'show_comments',true);

        $this->view->assign('show_comments', $show_comments);
        $this->view->assign('post', $post);
        $this->view->assign('cron_schedule_time', waSystem::getSetting('cron_schedule',0,'blog'));

        $locale = $this->getUser()->getLocale();

        $this->setLayout(new blogDefaultLayout());
        $this->getResponse()->setTitle($title);
    }

    /**
     * Calculate and format remaining interval of time
     * @param string $datetime
     */
    private function calculateRemainingTime($datetime)
    {

        $remaining_time = array();

        $interval = strtotime($datetime) - time();
        $ago = false;

        if($interval < 0) {
            $interval = -$interval;
            $ago = true;
        }

        $interval_divisor = 3600*24*365;
        if ($interval_chunk = floor($interval/$interval_divisor)) {
            $remaining_time[] = $interval_chunk." "._ws("year", "years", $interval_chunk);
        }
        $interval %= $interval_divisor;

        $interval_divisor = 3600*24*30;
        if ($interval_chunk = floor($interval/$interval_divisor)) {
            $remaining_time[] = $interval_chunk." "._ws("month", "months", $interval_chunk);
        }
        $interval %= $interval_divisor;

        $interval_divisor = 3600*24;
        if ($interval_chunk = floor($interval/$interval_divisor)) {
            $remaining_time[] =$interval_chunk." "._ws("day", "day", $interval_chunk);
        }
        $interval %= $interval_divisor;

        $interval_divisor = 3600;
        if ($interval_chunk = floor($interval/$interval_divisor)) {
            $remaining_time[] = $interval_chunk." "._ws("hour", "hours", $interval_chunk);
        }
        $interval %= $interval_divisor;

        $interval_divisor = 60;
        if ($interval_chunk = floor($interval/$interval_divisor)) {
            $remaining_time[] = $interval_chunk." "._ws("minute", "minutes", $interval_chunk);
        }

        return sprintf($ago?'<span class="red">'._w('%s ago').'</span>':_w('in %s'),implode(" ", $remaining_time));

    }

    /**
     * Check rights and return allowed blog
     *
     * @param array $blogs list of selection
     * @param int $blog_id prefered blog. If allowed return it
     */
    private function getAllowedBlog($blogs, $blog_id = null)
    {
        if (!is_null($blog_id) && isset($blogs[$blog_id]) && ($blogs[$blog_id]['rights'] >= blogRightConfig::RIGHT_READ_WRITE)) {
            return $blogs[$blog_id];
        }

        foreach ($blogs as $blog_id => $blog) {
            if ($blog['rights'] >= blogRightConfig::RIGHT_READ_WRITE) {
                return $blog;
            }
        }

        return false;
    }

    /**
     * Get custom params for post
     * @param int $post_id
     */
    private function getPostParams($post_id)
    {
        $params = array();

        if ($post_id) {
            $params_model = new blogPostParamsModel();
            $params = $params_model->select('name, value')->where('post_id = i:id', array('id' => $post_id))->fetchAll('name', true);
        }

        return $params;
    }
}
