<?php
class blogCommentModel extends waNestedSetModel
{
    const STATUS_DELETED = 'deleted';
    const STATUS_PUBLISHED = 'approved';

    const AUTH_USER = 'user';
    const AUTH_GUEST = 'guest';

    protected $table = 'blog_comment';

    /**
     * Get post comments by post ID
     *
     * @param $id
     * @param array $fields
     * @param array $options
     * @return mixed
     */
    public function get($id, $fields = array(), $options = array())
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE post_id = i:post_id ORDER BY `left`";
        $items = $this->query($sql, array('post_id' => $id))->fetchAll();
        return $this->prepareView($items, $fields, $options);
    }

    public function getList($offset = 0, $limit = 20, $blog_id, $fields = array(), $options = array())
    {
        if (!$blog_id) {
            return array();
        }
        $sql = <<<SQL
        SELECT node.id id,
			 node.text text,
			 node.post_id post_id,
			 node.blog_id blog_id,
			 node.status status,
			 node.contact_id contact_id,
			 node.name name,
			 node.email email,
			 node.datetime datetime,
			 node.ip ip,
			 node.site site,
			 node.auth_provider auth_provider,
			 node.parent,
			 parent.id parent_id,
			 parent.text parent_text,
			 parent.status parent_status,
			 parent.name parent_name,
			 parent.email parent_email
			FROM {$this->table} node
		LEFT JOIN {$this->table} AS parent ON parent.id = node.parent
		WHERE node.blog_id IN (:blog_id)
		ORDER BY node.datetime DESC
		LIMIT i:o, i:l
SQL;
        $items = $this->query($sql, array('l' => $limit, 'o' => $offset, 'blog_id' => $blog_id))->fetchAll('id');
        return $this->prepareView($items, $fields, $options);

    }

    public function prepareView($items, $fields = array(), $extend_options = array())
    {
        blogHelper::extendUser($items, $fields, !empty($extend_options['user']));
        $contact_id = wa()->getUser()->getId();

        if (isset($extend_options['datetime'])) {
            $viewed_comments = array();
            $expire = isset($extend_options['expire']) ? $extend_options['expire'] : false;
        }

        #data holders for plugin hooks
        foreach ($items as &$item) {
            $item['plugins'] = array(
                'before'            => array(),
                'after'             => array(),
                'authorname_suffix' => array(),
            );
            $item['ip'] = long2ip($item['ip']);
            if (empty($item['name']) && !empty($item['user']['name'])) {
                $item['name'] = $item['user']['name'];
            }
            if (isset($extend_options['datetime'])) {

                if (($item['datetime'] > $extend_options['datetime']) && (!$contact_id || ($contact_id != $item['contact_id']))) {
                    $item['new'] = blogActivity::getInstance()->isNew("c.{$item['post_id']}", $item['id'], $expire);
                    if ($item['new'] == blogActivity::STATE_NEW) {
                        $viewed_comments[$item['post_id']][] = $item['id'];
                    } elseif (!$item['new']) {
                        unset($item['new']);
                    }
                }
            }
            if (!$item['auth_provider']) {
                if ($item['contact_id']) {
                    $item['auth_provider'] = blogCommentModel::AUTH_USER;
                } else {
                    $item['auth_provider'] = blogCommentModel::AUTH_GUEST;
                }
            }
            if (!empty($extend_options['escape'])) {
                $item['text'] = htmlspecialchars($item['text'], ENT_QUOTES, 'utf-8');
                $item['name'] = htmlspecialchars($item['name'], ENT_QUOTES, 'utf-8');
            }
            unset($item);
        }

        if (!empty($viewed_comments)) {
            foreach ($viewed_comments as $post_id => $ids) {
                blogActivity::getInstance()->set("c.{$post_id}", $ids);
            }
        }

        /**
         * Prepare comments data
         * Extend each comment item via plugins data
         * @event prepare_comments_frontend
         * @event prepare_comments_backend
         * @param array[string]mixed $items
         * @param array[string]int $items[%id][id] Comment ID
         * @param array[string][string][string]string $item[plugins][before][%plugin_id%]
         * @param array[string][string][string]string $item[plugins][after][%plugin_id%]
         * @param array[string][string][string]string $item[plugins][authorname_suffix][%plugin_id%]
         * @return void
         */
        wa()->event('prepare_comments_'.wa()->getEnv(), $items);
        return $items;
    }


    public function getCount($blog_id, $post_id = null, $datetime = null, $expire = null, $post_contact_id = null, $status = self::STATUS_PUBLISHED)
    {
        $where = array();
        $join = array();
        if ($datetime) {
            $where[] = "{$this->table}.datetime > '{$this->escape($datetime)}'";
            if ($contact_id = wa()->getUser()->getId()) {
                $where[] = "{$this->table}.contact_id != ".intval($contact_id);
            }
        }
        if ($post_contact_id = max(0, intval($post_contact_id))) {
            $post_model = new blogPostModel();
            $post_table = $post_model->getTableName();
            $post_table_id = $post_model->getTableId();
            $join[] = " INNER JOIN {$post_table} ON {$post_table}.{$post_table_id} = {$this->table}.post_id";
            $where[] = "{$post_table}.contact_id = {$post_contact_id}";
        }
        if ($status) {
            $where[] = $this->getWhereByField('status', $status, true);
        }
        if ($post_id) {
            $where[] = $this->getWhereByField('post_id', $post_id, true);
        }
        if ($blog_id !== null) {
            $where[] = $this->getWhereByField('blog_id', $blog_id, true);
        }

        if ($datetime) {
            $count_by_post = $post_id && is_array($post_id);
            if ($count_by_post) {
                $count = array_fill_keys($post_id, 0);
            } else {
                $count = 0;
            }
            $sql = "SELECT {$this->table}.{$this->id} AS {$this->id}, post_id FROM {$this->table} ".implode('', $join)." WHERE (".implode(') AND (', $where).")";
            if ($comments = $this->query($sql)->fetchAll($this->id, true)) {
                $blog_activity = blogActivity::getInstance();
                foreach ($comments as $id => $comment_post_id) {
                    if ($blog_activity->isNew("c.{$comment_post_id}", $id, $expire)) {
                        if ($count_by_post) {
                            ++$count[$comment_post_id];
                        } else {
                            ++$count;
                        }
                    }
                }
            }
        } elseif ($post_id && is_array($post_id)) {
            $sql = "SELECT post_id, COUNT(*) FROM {$this->table} ".implode('', $join)." WHERE (".implode(') AND (', $where).") GROUP BY post_id";
            $count = $this->query($sql)->fetchAll('post_id', true);
        } else {
            $sql = "SELECT COUNT(*) FROM {$this->table} ".implode('', $join)." WHERE (".implode(') AND (', $where).")";
            $count = $this->query($sql)->fetchField();
        }
        return $count;
    }

    public function getDatetime($post_id = array())
    {
        $sql = "SELECT post_id, MAX(datetime) FROM {$this->table}";
        if ($post_id) {
            $sql .= ' WHERE '.$this->getWhereByField('post_id', $post_id);
        }
        $sql .= ' GROUP BY post_id';
        return $this->query($sql)->fetchAll('post_id', true);
    }

    public function add($comment, $parent = null, $before_id = null)
    {
        if (!isset($comment['ip']) && ($ip = waRequest::getIp())) {
            $ip = ip2long($ip);
            if ($ip > 2147483647) {
                $ip -= 4294967296;
            }
            $comment['ip'] = $ip;
        }

        if (!isset($comment['datetime'])) {
            $comment['datetime'] = date('Y-m-d H:i:s');
        }

        if (isset($comment['site']) && $comment['site']) {
            if (!preg_match('@^https?://@', $comment['site'])) {
                $comment['site'] = 'http://'.$comment['site'];
            }
        }

        $comment[$this->parent] = $parent;

        blogActivity::setUserActivity();

        /**
         * @event comment_presave_frontend
         * @event comment_presave_backend
         * @param array $comment
         * @param int $comment.id
         * @param int $comment.parent
         * @return void
         */
        wa()->event('comment_presave_'.wa()->getEnv(), $comment);
        $before_id = null;
        $comment['id'] = parent::add($comment, $parent, $before_id);
        /**
         * @event comment_save_frontend
         * @event comment_save_backend
         * @param array $comment
         * @param int $comment.id
         * @param int $comment.parent
         * @return void
         */
        wa()->event('comment_save_'.wa()->getEnv(), $comment);
        return $comment['id'];
    }


    /**
     * Delete records from table and fire evenets
     *
     * @param $field
     * @param $value
     * @return bool
     */
    public function deleteByField($field, $value = null)
    {
        if (is_array($field)) {
            $items = $this->getByField($field, $this->id);
        } else {
            $items = $this->getByField($field, $value, $this->id);
        }
        $res = false;
        if ($comment_ids = array_keys($items)) {
            /**
             * @event comment_predelete
             * @param array $comment_ids array of comment's ID
             * @return void
             */
            wa()->event('comment_predelete', $comment_ids);
            $res = parent::deleteByField('id', $comment_ids);
            if ($res) {
                /**
                 * @event comment_delete
                 * @param array $comment_ids array of comment's ID
                 * @return void
                 */
                wa()->event('comment_delete', $comment_ids);
            }
        }
        return $res;
    }

    public function validate($comment)
    {
        $errors = array();
        if (empty($comment['auth_provider'])) {
            $comment['auth_provider'] = self::AUTH_GUEST;
        }
        switch ($comment['auth_provider']) {
            case self::AUTH_GUEST:
                if (!empty($comment['site']) && strpos($comment['site'], '://') === false) {
                    $comment['site'] = "http://".$comment['site'];
                }

                if (empty($comment['name']) || (mb_strlen($comment['name']) == 0)) {
                    $errors[]['name'] = _w('Name can not be left blank');
                }
                if (mb_strlen($comment['name']) > 255) {
                    $errors[]['name'] = _w('Name length should not exceed 255 symbols');
                }
                if (empty($comment['name']) || (mb_strlen($comment['email']) == 0)) {
                    $errors[]['email'] = _w('Email can not be left blank');
                }
                $validator = new waEmailValidator();
                if (!$validator->isValid($comment['email'])) {
                    $errors[]['email'] = _w('Email is not valid');
                }
                $validator = new waUrlValidator();
                if (!empty($comment['site']) && !$validator->isValid($comment['site'])) {
                    $errors[]['site'] = _w('Site URL is not valid');
                }
                break;
            case self::AUTH_USER:
                $user = wa()->getUser();
                if ($user->getId() && !$user->get('is_user')) {
                    $user->addToCategory(wa()->getApp());
                }
                break;
            default:
                break;
        }

        if (mb_strlen($comment['text']) == 0) {
            $errors[]['text'] = _w('Comment text can not be left blank');
        }
        if (mb_strlen($comment['text']) > 4096) {
            $errors[]['text'] = _w('Comment length should not exceed 4096 symbols');
        }

        /**
         * @event comment_validate
         * @param array[string]mixed $data
         * @param array['plugin']['%plugin_id%']mixed plugin data
         * @return array['%plugin_id%']['field']string error
         */
        $plugin_errors = wa()->event('comment_validate', $comment);
        if (is_array($plugin_errors)) {
            foreach ($plugin_errors as $plugin) {
                if ($plugin !== true) {
                    if ($plugin) {
                        $errors[] = $plugin;
                    } else {
                        $errors[]['text'] = _w('Invalid data');
                    }
                }
            }
        }


        return $errors;

    }

    public static function extendRights($comments, $posts = array())
    {
        foreach ($comments as &$comment) {
            if (isset($posts[$comment['post_id']])) {
                $comment['post'] = $posts[$comment['post_id']];
                $comment['rights'] = $comment['post']['rights'];
                $comment['editable'] = $comment['post']['editable'];
            } else {
                $comment['post'] = false;
                $comment['rights'] = blogRightConfig::RIGHT_NONE;
                $comment['editable'] = false;;
            }
            unset($comment);
        }
        return $comments;
    }
}