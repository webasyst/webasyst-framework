<?php

class blogPostSaveController extends waJsonController
{

    const OPERATION_PUBLISH = 'publish';
    const OPERATION_SAVE_DRAFT = 'save_draft';
    const OPERATION_SET_DEADLINE = 'set_deadline';
    const OPERATION_SET_SCHEDULE = 'set_schedule';
    const OPERATION_UNPUBLISH = 'unpublish';
    const OPERATION_DELETE = 'delete';
    const OPERATION_CANCEL_SCHEDULE = 'cancel_schedule';

    private $validate_messages = array();

    /**
     *
     * @var blogPostModel
     */
    private $post_model = null;

    private $operation = null;

    private $inline = false;

    public function execute()
    {
        $this->post_model = new blogPostModel();

        $post = $this->getPreparedPost();
        $this->makeOperation($post);
    }

    /**
     * Prepare for saving posted post and return it
     *
     * @return array prepared post
     *
     */
    private function getPreparedPost()
    {
        $post = array(
            'id'                 => waRequest::post('post_id', null, waRequest::TYPE_INT),
            'title'              => substr(waRequest::post('title', '', waRequest::TYPE_STRING_TRIM), 0, 255),
            'text'               => waRequest::post('text'),
            'blog_id'            => waRequest::post('blog_id'),
            'contact_id'         => waRequest::post('contact_id'),
            'datetime'           => waRequest::post('datetime'),
            'url'                => waRequest::post('url','', waRequest::TYPE_STRING_TRIM),
            'draft'              => waRequest::post('draft'),
            'comments_allowed'   => max(0,min(1,waRequest::post('comments_allowed', 0, waRequest::TYPE_INT))),
            'public'             => waRequest::post('public'),
            'schedule_datetime'  => waRequest::post('schedule_datetime'),
            'meta_title' => waRequest::post('meta_title', null, waRequest::TYPE_STRING_TRIM),
            'meta_keywords' => waRequest::post('meta_keywords', null, waRequest::TYPE_STRING_TRIM),
            'meta_description' => waRequest::post('meta_description', null, waRequest::TYPE_STRING_TRIM)
        );

        $this->inline = waRequest::post('inline', false);

        if (waRequest::post('scheduled') && !empty($post['schedule_datetime'])) {
            $post['datetime'] = $post['schedule_datetime'];
        }

        if (!is_null($post['datetime'])) {

            $post['datetime'] = (array) $post['datetime'];

            if (count($post['datetime']) == 3) {
                $post['datetime'][1] = (int) $post['datetime'][1];
                $post['datetime'][2] = (int) $post['datetime'][2];
                $date_time = $post['datetime'][0] . ' ' . $post['datetime'][1] . ':' . $post['datetime'][2];
            } else {
                $date_time = implode(' ', $post['datetime']);
            }

            $post['datetime'] = $date_time;
        }

        if (waRequest::post('draft'))
        {
            $post['status'] = blogPostModel::STATUS_DRAFT;
            $this->operation = self::OPERATION_SAVE_DRAFT;
        }
        else if (waRequest::post('deadline'))
        {
            if ($post['datetime']) {
                $post['status'] = blogPostModel::STATUS_DEADLINE;
                $this->operation = self::OPERATION_SET_DEADLINE;
            } else {
                $post['status'] = blogPostModel::STATUS_DRAFT;
                $this->operation = self::OPERATION_SAVE_DRAFT;
            }
        }
        else if (waRequest::post('scheduled'))
        {
            $post['status'] = blogPostModel::STATUS_SCHEDULED;
        }
        else if (waRequest::post('published'))
        {
            $post['status'] = blogPostModel::STATUS_PUBLISHED;
            $this->operation = self::OPERATION_PUBLISH;
        }
        else if (waRequest::post('unpublish'))
        {
            $post['status'] = blogPostModel::STATUS_DRAFT;
            $this->operation = self::OPERATION_UNPUBLISH;
        }
        else if ($post['id'] && waRequest::issetPost('delete'))
        {
            $this->operation = self::OPERATION_DELETE;
        }
        else if (waRequest::issetPost("schedule_cancel"))
        {
            $this->operation = self::OPERATION_CANCEL_SCHEDULE;
        }

        if (!isset($post['status'])) {
            if ($post['id']) {
                $post['status'] = $this->post_model->select('status')
                ->where('id = i:id', array('id' => $post['id']))
                ->fetchField('status');
            } else {
                $post['status'] = blogPostModel::STATUS_DRAFT;
            }
        }

        $blog_model = new blogBlogModel();
        $blog = $blog_model->getById($post['blog_id']);
        $post['blog_status'] = $blog['status'];

        $post['plugin'] = (array)waRequest::post('plugin', null);
        foreach ($post['plugin'] as $k => &$plugin_data) {
            if(!is_array($plugin_data)) {
                $plugin_data = trim($plugin_data);
            }
        }

        return $post;

    }

    private function makeOperation($post)
    {
        switch ($this->operation) {
            case self::OPERATION_DELETE:
                $this->delete($post);
                break;
            case self::OPERATION_CANCEL_SCHEDULE:
                $this->cancelSchedule($post);
                break;
            default:
                $this->save($post);
                break;
        }
    }

    private function save($post)
    {
        $options = array();
        if (waRequest::post('transliterate', null)) {
            $options['transliterate'] = true;
        }

        $this->validate_messages = $this->post_model->validate($post, $options);

        if ($this->validate_messages) {
            $this->errors = $this->validate_messages;
        } else {

            $post['text_before_cut'] = null;
            $post['cut_link_label'] = null;

            $template = '<!--[\s]*?more[\s]*?(text[\s]*?=[\s]*?[\'"]([\s\S]*?)[\'"])*[\s]*?-->';
            $descriptor = preg_split("/$template/", $post['text'], 2, PREG_SPLIT_DELIM_CAPTURE);
            if ($descriptor) {
                if (count($descriptor) == 2) {
                    $post['text_before_cut'] = blogPost::closeTags($descriptor[0]);
                } elseif (count($descriptor) > 2) {
                    $post['text_before_cut'] = blogPost::closeTags($descriptor[0]);
                    if (isset($descriptor[2])) {
                        $post['cut_link_label'] = $descriptor[2];
                    }
                }
            }
            if ($post['id']) {
                $prev_post = $this->post_model->getFieldsById($post['id'], 'status');
                if ($prev_post['status'] != blogPostModel::STATUS_PUBLISHED && $post['status'] == blogPostModel::STATUS_PUBLISHED) {
                    $this->inline = false;
                }
                $this->post_model->updateItem($post['id'], $post);
                if ($prev_post['status'] != blogPostModel::STATUS_PUBLISHED && $post['status'] == blogPostModel::STATUS_PUBLISHED) {
                    $this->log('post_publish', 1);
                } else {
                    $this->log('post_edit', 1);
                }
            } else {
                $post['id'] = $this->post_model->updateItem(null, $post);
                $this->log('post_publish', 1);
            }

            $this->saveParams($post['id']);

            $this->clearViewCache($post['id'], $post['url']);

            if (!$this->inline) {
                if ($post['status'] != blogPostModel::STATUS_PUBLISHED) {
                    $params = array(
                        'module' => 'post',
                        'action' => 'edit',
                        'id' => $post['id'],
                    );
                } elseif ($post['blog_status'] == blogBlogModel::STATUS_PUBLIC) {
                    $params = array(
                        'blog' => $post['blog_id'],
                    );
                } else {
                    $params = array(
                        'module' => 'post',
                        'id' => $post['id'],
                    );
                }
                $this->response['redirect'] = $this->getRedirectUrl($params);
            } else {
                $this->response['formatted_datetime'] = waDateTime::format('humandatetime', $post['datetime']);
                $this->response['id'] = $post['id'];
                $this->response['url'] = $post['url'];
                if ($post['status'] != blogPostModel::STATUS_PUBLISHED) {
                    $options = array(
                        'contact_id' => $post['contact_id'],
                        'blog_id' => $post['blog_id'],
                        'post_id' => $post['id'],
                        'user_id' => wa()->getUser()->getId()
                    );
                    $preview_hash = blogPostModel::getPreviewHash($options);
                    $this->response['preview_hash'] = base64_encode($preview_hash.$options['user_id']);
                    $this->response['debug'] = $options;
                }
            }
        }
    }

    private function delete($post)
    {
        $post_model = new blogPostModel();
        $post = $post_model->getFieldsById($post['id'], array('id', 'blog_id'));
        if ($post) {
            if (!$this->getUser()->isAdmin($this->getApp())) {
                // author of post
                if ( $post['contact_id'] == $this->getUser()->getId() ) {
                    blogHelper::checkRights($post['blog_id'], $this->getUser()->getId(),blogRightConfig::RIGHT_READ_WRITE);
                } else {
                    blogHelper::checkRights($post['blog_id'], $this->getUser()->getId(),blogRightConfig::RIGHT_FULL);
                }
            }
            $post_model->deleteById($post['id']);
            $this->response['redirect'] = '?blog='.$post['blog_id'];
        } else {
            $this->response['redirect'] = '?';
        }
    }

    private function cancelSchedule($post)
    {
        $this->post_model->updateById($post['id'], array("schedule_datetime" => null));
        $this->response['redirect'] = $this->getRedirectUrl(array(
            'module' => 'post',
            'action' => 'edit',
            'id' => $post['id'],
        ));
    }

    private function clearViewCache($post_id, $post_url)
    {
        if ($this->getConfig()->getOption('cache_time')) {

            $view = waSystem::getInstance()->getView();
            $template = 'post.html';
            $template_path = wa()->getDataPath('themes/default', true) . '/' . $template;

            if (file_exists($template_path)) {
                $template = 'file:' . $template_path;
            } else {
                $template = 'templates/themes/default/' . $template;
            }

            $view->clearCache($template, 'id_' . $post_id);
            $view->clearCache($template, 'url_' . $post_url);
        }
    }

    private function getRedirectUrl($params)
    {
        return waSystem::getInstance()->getUrl() . '?' . http_build_query($params);
    }

    /**
     * Save custom params for post
     * @param int $post_id
     */
    private function saveParams($post_id)
    {
        $params = array();
        $post_params = waRequest::post('params', '', waRequest::TYPE_STRING_TRIM);
        if ($post_params) {
            $post_params = explode("\n", $post_params);
            foreach ($post_params as $param) {
                $param = explode("=", trim($param), 2);
                if (count($param) == 2) {
                    $params[$param[0]] = $param[1];
                }
            }
        }

        $params_model = new blogPostParamsModel();

        $old_params = !$post_id ? array() : $params_model->select('name,value')->where('post_id = i:id', array('id' => $post_id))->fetchAll('name', true);

        if ($params || $old_params) {
            $add = array();
            $update = array();
            foreach ($params as $param => $value) {
                if (isset($old_params[$param])) {
                    if ($value != $old_params[$param]) {
                        $update[$param] = $value;
                    }
                    unset($old_params[$param]);
                } else {
                    $add[$param] = $value;
                }
            }
            $delete = $old_params;
            if ($delete) {
                $params_model->deleteByField(array('post_id' => $post_id, 'name' => array_keys($delete)));
            }
            if ($add) {
                foreach ($add as $name => $value) {
                    $params_model->insert(array('post_id' => $post_id, 'name' => $name, 'value' => $value));
                }
            }
            if ($update) {
                foreach ($update as $name => $value) {
                    $params_model->updateByField(array('post_id' => $post_id, 'name' => $name), array('value' => $value));
                }
            }
        }
    }

}
