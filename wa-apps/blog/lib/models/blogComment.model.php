<?php
class blogCommentModel extends waNestedSetModel
{
    const STATUS_DELETED	 = 'deleted';
    const STATUS_PUBLISHED	 = 'approved';

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
        $items = $this->query($sql, array('post_id'=>$id))->fetchAll();
        return $this->prepareView($items, $fields, $options);
    }

    public function getList($offset = 0, $limit = 20,$blog_id, $fields = array(), $options = array())
    {
        $sql = <<<SQL
        SELECT node.id as id,
			 node.text as text,
			 node.post_id as post_id,
			 node.blog_id as blog_id,
			 node.status as status,
			 node.contact_id as contact_id,
			 node.name as name,
			 node.email as email,
			 node.datetime as datetime,
			 node.ip as ip,
			 node.site as site,
			 node.auth_provider as auth_provider,
			 node.parent,
			 parent.id as parent_id,
			 parent.text as parent_text,
			 parent.status as parent_status,
			 parent.name as parent_name,
			 parent.email as parent_email
			FROM {$this->table} as node
		LEFT JOIN {$this->table} as parent ON parent.id = node.parent
		WHERE node.blog_id IN (:blog_id)
		ORDER BY node.datetime DESC
		LIMIT i:offset, i:limit
SQL;
        $items = $this->query($sql, array('limit'=>$limit, 'offset'=>$offset,'blog_id'=>$blog_id))->fetchAll('id');
        return $this->prepareView($items, $fields, $options);

    }

    public function prepareView($items, $fields = array(), $options = array())
    {
        blogHelper::extendUser($items,$fields,isset($options['user']) && $options['user']);
        $contact_id = wa()->getUser()->getId();
        #data holders for plugin hooks
        foreach ($items as &$item) {
            $item['plugins'] = array(
            	'before'=>array(),
            	'after'=>array(),
            	'authorname_suffix'=>array(),
            );
            $item['ip'] = long2ip($item['ip']);
            if (isset($options['datetime'])) {
                if ( ($item['datetime'] > $options['datetime']) && (!$contact_id || ($contact_id != $item['contact_id'])) ){
                    $item['new'] = true;
                }
            }
            if (!$item['auth_provider']) {
                if ($item['contact_id']) {
                    $item['auth_provider'] = blogCommentModel::AUTH_USER;
                } else {
                    $item['auth_provider'] = blogCommentModel::AUTH_GUEST;
                }
            }
            unset($item);
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


    public function getCount($datetime,$blogs = null)
    {
        $where = array();
        $where[] = "datetime > '{$this->escape($datetime)}'";
        $where[] = $this->getWhereByField('status',self::STATUS_PUBLISHED);
        if ($blogs !== null) {
            $where[] = $this->getWhereByField('blog_id',$blogs);
        }
        return $this->select('COUNT(id)')->where('('.implode(') AND (',$where).')')->fetchField();
    }

    public function getCountByPost($post_ids, $status, $datetime = null)
    {
        $param = '';
        $contact_id = null;
        if ($datetime) {
            if ($contact_id = wa()->getUser()->getId()) {
                $param = ',SUM(IF((datetime > :datetime) AND (contact_id != i:contact_id), 1, 0)) as new ';
            } else {
                $param = ',SUM(IF(datetime > :datetime, 1, 0)) as new ';
            }
        }

        $where = '';
        if (!empty($post_ids)) {
            $where = "WHERE ". $this->getWhereByField('post_id', $post_ids)." AND ".$this->getWhereByField('status',$status);
        }

        $sql = <<<SQL
        SELECT post_id, COUNT(post_id) as count, MAX(datetime) as datetime {$param}
		FROM {$this->table}
		{$where}
		GROUP BY post_id
SQL;

		return $this->query($sql, array('datetime'=>$datetime,'contact_id'=>$contact_id))->fetchAll('post_id');
    }

    public function countByStatus($post_id, $status = self::STATUS_PUBLISHED)
    {
        $sql = "SELECT COUNT({$this->id}) FROM {$this->table}";
        $where = array();
        $where[] = $this->getWhereByField('post_id',$post_id);
        if ($status !== false) {
            $where[] = $this->getWhereByField('status',$status);
        }

        $sql .= " WHERE ".implode(' AND ',$where);
        return $this->query($sql)->fetchField();
    }

    public function countByParam($blogs = null,$datetime = null,$status = null, $post_contact_id = null)
    {

        $sql = "SELECT COUNT({$this->table}.id) FROM {$this->table}";
        $where = array();
        if ($status) {
            $where[] = $this->getWhereByField('status',$status,true);
        }
        if ($blogs !== null) {
            $where[] = $this->getWhereByField('blog_id',$blogs,true);
        }
        if ($datetime) {
            $where[] = "{$this->table}.datetime > '{$this->escape($datetime)}'";
            $where[] = "{$this->table}.contact_id != '{$this->escape($post_contact_id?$post_contact_id:wa()->getUser()->getId())}'";
        }

        if ($post_contact_id = max(0,intval($post_contact_id))) {
            $post_model = new blogPostModel();
            $post_table = $post_model->getTableName();
            $post_id = $post_model->getTableId();
            $sql .=" INNER JOIN {$post_table} ON {$post_table}.id = {$this->table}.post_id";
            $where[] = "{$post_table}.contact_id = {$post_contact_id}";
        }

        if ($where) {
            $sql .= " WHERE (".implode(') AND (',$where).')';
        }
        return $this->query($sql)->fetchField();
    }

    public function add($comment, $parent = null)
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
            if (!preg_match('@^https?://@',$comment['site'])) {
                $comment['site'] = 'http://'.$comment['site'];
            }
        }

        $comment[$this->parent] = $parent;

        blogHelper::setLastActivity();

        /**
         * @event comment_presave_frontend
         * @event comment_presave_backend
         * @param array $comment
         * @param int $comment.id
         * @param int $comment.parent
         * @return void
         */
        wa()->event('comment_presave_'.wa()->getEnv(), $comment);
        $comment['id'] = parent::add($comment, $parent);
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
     * @param $value
     * @return bool
     */
    public function deleteByField($field, $value = null)
    {
        if (is_array($field)) {
            $items = $this->getByField($field,$this->id);
        } else {
            $items = $this->getByField($field,$value,$this->id);
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
        switch($comment['auth_provider']) {
            case self::AUTH_GUEST:{
                if (!empty($comment['site']) && strpos($comment['site'], '://')===false) {
                    $comment['site'] = "http://" . $comment['site'];
                }

                if (mb_strlen( $comment['name'] ) == 0) {
                    $errors[]['name'] = _w('Name can not be left blank');
                }
                if (mb_strlen( $comment['name'] ) > 255) {
                    $errors[]['name'] = _w('Name length should not exceed 255 symbols');
                }
                if (mb_strlen( $comment['email'] ) == 0) {
                    $errors[]['email'] = _w('Email can not be left blank');
                }
                $validator = new waEmailValidator();
                if (!$validator->isValid($comment['email'])) {
                    $errors[]['email'] = _w('Email is not valid');
                }
                $validator = new waUrlValidator();
                if ($comment['site'] && !$validator->isValid($comment['site'])) {
                    $errors[]['site'] = _w('Site URL is not valid');
                }
                break;
            }
            default: {
                break;
            }
        }

        if (mb_strlen( $comment['text'] ) == 0) {
            $errors[]['text'] = _w('Comment text can not be left blank');
        }
        if (mb_strlen( $comment['text'] ) > 4096) {
            $errors[]['text'] = _w('Comment length should not exceed 4096 symbols');
        }

        /**
         * @event comment_validate
         * @param array[string]mixed $data
         * @param array['plugin']['%plugin_id%']mixed plugin data
         * @return array['%plugin_id%']['field']string error
         */
        $plugin_erros = wa()->event('comment_validate',$comment);
        if(is_array($plugin_erros)) {
            foreach ($plugin_erros as $plugin) {
                if ($plugin !== true) {
                    if($plugin) {
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