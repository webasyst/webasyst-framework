<?php
class blogPostModel extends blogItemModel
{
    const STATUS_DRAFT = 'draft';
    const STATUS_DEADLINE = 'deadline';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHED = 'published';

    protected $table = 'blog_post';

    /**
     * @param array $blogs
     * @param array $blog_data
     * @param array $datetime
     * @return array
     */
    public function getTimeline($blogs = array(), $blog_data = array(), $datetime = array())
    {
        static $cache = array();
        $datetime = array(
            'year'  => ifset($datetime['year']),
            'month' => ifset($datetime['month']),
        );
        $key = md5(var_export(compact('blogs', 'datetime'), true));
        if (!isset($cache[$key])) {
            $where = array();
            $sql = <<<SQL
SELECT
    COUNT(*) AS `count`,
    EXTRACT(YEAR_MONTH FROM `datetime`) AS `timeline`,
    EXTRACT(MONTH FROM `datetime`) AS `month`,
    EXTRACT(YEAR FROM `datetime`) AS `year`,
    `blog_id`
FROM `{$this->table}`
SQL;
            $use_blog_id = true;
            $where[] = $this->getWhereByField('status', self::STATUS_PUBLISHED);
            if ($blogs !== false) {
                $where[] = $this->getWhereByField('blog_id', $blogs);
                if (count($blogs) > 1) {
                    $use_blog_id = false;
                }
            } else {
                $use_blog_id = false;
            }
            $sql .= "\nWHERE ".implode("\nAND\n\t", $where);
            $sql .= <<<SQL

GROUP BY EXTRACT(YEAR_MONTH FROM `datetime`)
ORDER BY `timeline` DESC
SQL;
            $posts = $this->query($sql)->fetchAll('timeline');

            $links = array();

            foreach ($posts as &$post) {
                if (isset($post['month'])) {
                    $post['month'] = sprintf('%02d', $post['month']);
                }
                if ($use_blog_id) {
                    if (!empty($blog_data[$post['blog_id']])) {
                        $post['blog_url'] = $blog_data[$post['blog_id']]['url'];
                    }
                } else {
                    unset($post['blog_id']);
                }
                $post['link'] = blogPost::getUrl($post, 'timeline');
                $year = $post['year'];
                if (!isset($links[$year])) {
                    $t = $post;
                    unset($t['month']);
                    unset($t['link']);
                    $links[$year] = blogPost::getUrl($t, 'timeline');
                }

                $post['selected'] = false;
                $post['year_selected'] = false;
                if (!empty($datetime['year'])) {
                    if ($datetime['year'] == $post['year']) {
                        if (!empty($datetime['month'])) {
                            $post['selected'] = ($datetime['month'] == $post['month']);
                        } else {
                            $post['year_selected'] = true;
                        }
                    }
                }
                $post['year_link'] = $links[$year];
                unset($post);
            }
            krsort($posts);
            $cache[$key] = $posts;
        }
        return $cache[$key];
    }

    public function getBlogPost($id, $blog_id = null)
    {
        $condition = array();
        $condition['id'] = $id;
        if ($blog_id) {
            $condition['blog_id'] = $blog_id;
        }
        $select = implode(', ', $this->setFields(false));
        $where = $this->getWhereByField($condition, true);
        return $this->select($select)->where($where)->fetch();
    }

    protected function setFields($fields = array(), $add_table = true)
    {
        if (isset($this->extend_options['text'])) {
            if ($fields) {
                foreach ($fields as $id => $field) {
                    if (($id == 'text') || ($field == 'text')) {
                        unset($fields[$id]);
                    }
                }
            } else {
                $fields = array();
                foreach ($this->fields as $field => $info) {
                    if ($info['type'] != 'text') {
                        $fields[] = $field;
                    }
                }
            }
            switch ($this->extend_options['text']) {
                case 'full':
                    $fields['text'] = "{$this->table}.text";
                    break;
                case 'preview':
                    $fields['text'] = "SUBSTRING(IFNULL({$this->table}.text_before_cut, {$this->table}.text), 400)";
                    break;
                case 'cut':
                default:
                    $fields['text'] = "IFNULL({$this->table}.text_before_cut, {$this->table}.text)";
                    $fields['cutted'] = "(CASE WHEN {$this->table}.text_before_cut IS NULL THEN 0 ELSE 1 END)";
                    break;
            }
        }
        return parent::setFields($fields, $add_table);
    }

    /**
     * Generic search post entries method
     * @param array $options <pre>array(
     *     ['year'=>2011|array(2009,2011)|array(2007,2008,...),]
     *     ['month'=>11|array(06,09),]
     *     ['day'=>30|array(12,23),]
     *     ['datetime'=>30|array(12,23),]
     *     ['status'=>false|int|array(),]
     *     ['contact_id'=>int,]
     *     ['blog_id'=>int|array,]
     *     ['text'=>string]
     * )</pre>
     * <p>Date option if single exact match, interval if array of two items, one of items in array more then two items<p>
     * <p>Status has default self::STATUS_PUBLISHED if not specified, all statuses if false or specified in array</p>
     * <p>If specified "contact_id" records will be checked authorship in non self::STATUS_PUBLISHED status</p>
     * @param array $extend_options
     * @param array $extend_data
     * @see blogItemModel::search()
     * @return blogPostModel
     */
    public function search($options = array(), $extend_options = array(), $extend_data = array())
    {
        parent::search($options, $extend_options, $extend_data);
        $this->sql_params['where'] = array();

        $option_names = array();

        $option_names['status'] = self::STATUS_PUBLISHED;
        $option_names['blog_id'] = false;
        $option_names['id'] = false;
        $option_names['url'] = false;



        $date_options = array(
            'year'     => 'YEAR(datetime)',
            'month'    => 'MONTH(datetime)',
            'day'      => 'DAYOFMONTH(datetime)',
            'datetime' => 'datetime',
        );
        foreach ($date_options as $field => $expression) {
            if (isset($options[$field]) && $options[$field]) {
                if (is_array($options[$field])) {
                    if (count($options[$field]) == 2) {
                        if ($this->sql_params[$field.'_min'] = array_shift($options[$field])) {
                            $this->sql_params['where'][] = "{$expression} >= :{$field}_min";
                        }

                        if ($this->sql_params[$field.'_max'] = array_shift($options[$field])) {
                            $this->sql_params['where'][] = "{$expression} <= :{$field}_max";
                        }
                    } else {
                        $values = array_map('intval', $options[$field]);
                        $this->sql_params['where'][] = "{$expression} IN (".implode(',', $values).")";
                    }
                } else {
                    $this->sql_params[$field] = $options[$field];
                    $this->sql_params['where'][] = "{$expression} = :{$field}";
                }
            }
        }

        if (isset($options['contact_id']) && $options['contact_id']) {
            if (isset($extend_data['blog'])) {
                $writable_blog = array('write' => array(), 'full' => array());
                foreach ($extend_data['blog'] as $id => $blog) {
                    if ($blog['rights'] >= blogRightConfig::RIGHT_FULL) {
                        $writable_blog['full'][] = $id;
                    } elseif ($blog['rights'] >= blogRightConfig::RIGHT_READ_WRITE) {
                        $writable_blog['write'][] = $id;
                    }
                }
            }
            if (isset($options['status']) && ($options['status'] === false)) {
                $options['status'] = array(self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_DEADLINE, self::STATUS_PUBLISHED);
            }
            if (isset($options['status'])) {
                if (isset($writable_blog)) {
                    $where = array();
                    $statuses = array_intersect(array(self::STATUS_PUBLISHED), $options['status']);
                    if ($statuses) {
                        $where[] = $this->getWhereByField('status', $statuses);
                    }

                    $statuses = array_intersect(array(self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_DEADLINE), $options['status']);
                    if ($statuses) {
                        if ($writable_blog['write']) {

                            $where[] = "(".$this->getWhereByField('status', $statuses)
                                ." AND "
                                .$this->getWhereByField('blog_id', $writable_blog['write'])
                                ." AND "
                                .$this->getWhereByField('contact_id', $options['contact_id'])
                                .")";
                        }
                        if ($writable_blog['full']) {
                            $where[] = "(".$this->getWhereByField('status', $statuses)
                                ." AND "
                                .$this->getWhereByField('blog_id', $writable_blog['full'])
                                .")";
                        }
                    }

                    $this->sql_params['where'][] = "(".implode(' OR ', $where).")";
                } else {
                    if (!is_array($options['status'])) {
                        $options['status'] = array($options['status']);
                    }
                    $where = array();
                    $statuses = array_intersect(array(self::STATUS_PUBLISHED), $options['status']);
                    if ($statuses) {
                        $where[] = $this->getWhereByField('status', $statuses);
                    }
                    $statuses = array_intersect(array(self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_DEADLINE), $options['status']);
                    if ($statuses) {
                        $where[] = "(".$this->getWhereByField('status', $statuses)
                            ." AND "
                            .$this->getWhereByField('contact_id', $options['contact_id'])
                            .")";
                    }
                    if ($where) {
                        $this->sql_params['where'][] = "(".implode(' OR ', $where).")";
                    }
                }
            } elseif (isset($option_names['status'])) {
                $this->sql_params['where'][] = $this->getWhereByField('status', $option_names['status']);
                $this->sql_params['where'][] = $this->getWhereByField('contact_id', $options['contact_id']);
            } else {
                $this->sql_params['where'][] = $this->getWhereByField('contact_id', $options['contact_id']);
            }
            unset($option_names['status']);
        }

        foreach ($option_names as $field => $default) {
            if (isset($options[$field])) {
                if ($options[$field] !== false) {
                    $this->sql_params['where'][] = $this->getWhereByField($field, $options[$field]);
                }
            } elseif ($default) {
                $this->sql_params['where'][] = $this->getWhereByField($field, $default);
            }
        }

        if (isset($options['sort'])) {
            switch ($options['sort']) {
                case 'create':
                    $this->sql_params['order'] = "{$this->table}.{$this->id} DESC";
                    break;
                case 'overdue':
                    $time = time();
                    $this->sql_params['order'] = "IF({$this->table}.status = '".self::STATUS_DEADLINE."', ({$time} - UNIX_TIMESTAMP({$this->table}.datetime)), ({$this->table}.{$this->id} - {$time})) DESC";
                    break;
                default:
                    if (in_array($options['sort'], $this->fields)) {
                        $this->sql_params['order'] = "{$this->table}.{$options['sort']} DESC";
                    }
                    break;
            }
        } else {
            $this->sql_params['order'] = "{$this->table}.datetime DESC";
        }
        if (!empty($options['text'])) {
            $this->sql_params['like'] = "%".str_replace(array('%', '_'), array('\%', '\_'), $options['text'])."%";
            $this->sql_params['where'][] = "(blog_post.title LIKE s:like OR blog_post.text LIKE s:like)";
        }
        if (!isset($extend_options['plugin']) || $extend_options['plugin']) {
            /**
             * Build post search query
             * @event search_posts_backend
             * @event search_posts_frontend
             * @example public function postSearch($options)
             * {
             *     $result = null;
             *     //check se
             *     if (is_array($options) && isset($options['plugin'])) {
             *         if (isset($options['plugin'][$this->id])) {
             *             $result = array();
             *             $result['where'][] = 'contact_id = '.wa()->getUser()->getId();
             *             $response = wa()->getResponse();
             *             $title = $response->getTitle();
             *             $title = _wp('My posts');
             *             $response->setTitle($title);
             *         }
             *     }
             *     return $result;
             * }
             * @param array[string]mixed $options
             * @return array[string][string][]string $return['%plugin_id%']['join'] Join conditions
             * @return array[string][string][]string $return['%plugin_id%']['where'] Where conditions
             * @return array[string][string][]string $return['%plugin_id%']['order'] order conditions
             */
            $res = wa()->event('search_posts_'.wa()->getEnv(), $options);
            foreach ($res as $plugin_options) {
                foreach ($plugin_options as $properties => $values) {
                    if ($values) {
                        if (!is_array($values)) {
                            $values = array($values);
                        }
                        if (!isset($this->sql_params[$properties])) {
                            $this->sql_params[$properties] = $values;
                        } else {
                            $this->sql_params[$properties] = array_merge($this->sql_params[$properties], $values);
                        }
                    }
                }
            }
        }
        return $this;
    }

    public function prepareView($items, $extend_options = array(), $extend_data = array())
    {
        $extend_options = array_merge($this->extend_options, (array)$extend_options);
        $extend_data = array_merge($this->extend_data, (array)$extend_data);
        $extend_author_link = (!isset($extend_options['link']) || $extend_options['link']) && (!isset($extend_options['author_link']) || $extend_options['author_link']);
        // get user info & photo by post
        if ($item = current($items)) {
            if (isset($item['contact_id']) && (!isset($extend_options['user']) || $extend_options['user'])) {
                if (!isset($extend_options['user'])) {
                    $extend_options['user'] = array('photo_url_20');
                } elseif (!is_array($extend_options['user'])) {
                    $extend_options['user'] = preg_split('/,[\s]*/', $extend_options['user']);
                }
                blogHelper::extendUser($items, $extend_options['user'], $extend_author_link);
            }
            if (isset($item['id'])) {

                if (!empty($extend_options['datetime'])) {
                    $comment_model = new blogCommentModel();
                    $comment_datetime = $comment_model->getDatetime(array_keys($items));
                    foreach ($items as $id => &$item) {
                        $item['comment_datetime'] = isset($comment_datetime[$id]) ? $comment_datetime[$id] : 0;
                        unset($item);
                    }
                } elseif (!isset($extend_options['comments'])) {
                    blogHelper::extendPostComments($items);
                } elseif ($extend_options['comments']) {
                    $fields = array();
                    if ($extend_options['comments'] !== true) {
                        foreach ((array)$extend_options['comments'] as $size) {
                            $fields[] = "photo_url_{$size}";
                        }
                    }
                    $activity_datetime = blogActivity::getUserActivity();
                    $comment_model = new blogCommentModel();
                    $comment_options = array(
                        'user'     => $extend_author_link,
                        'datetime' => $activity_datetime,
                        'escape'   => !empty($extend_options['escape']),
                    );
                    foreach ($items as $id => &$item) {

                        $item['comments'] = $comment_model->get($id, $fields, $comment_options);

                        $item['comment_count'] = 0;
                        $item['comment_new_count'] = 0;

                        foreach ($item['comments'] as &$comment) {
                            if ($comment['status'] == blogCommentModel::STATUS_PUBLISHED) {
                                ++$item['comment_count'];
                                if (!empty($comment['new'])) {
                                    ++$item['comment_new_count'];
                                }
                            }
                            unset($comment);
                        }
                        unset($item);
                    }

                }

                if (!empty($extend_options['status'])) {
                    blogHelper::extendPostState($items, $extend_options['status']);
                }

                if (!empty($extend_options['rights']) && isset($extend_data['blog'])) {
                    blogHelper::extendRights($items, $extend_data['blog'], wa()->getUser()->getId());
                }

                if (!empty($extend_options['params'])) {
                    $params_model = new blogPostParamsModel();
                    $params = $params_model->getByField('post_id', array_keys($items), true);
                }
            }
        }
        if (isset($params)) {
            foreach ($params as $param) {
                if (isset($items[$param['post_id']])) {
                    $items[$param['post_id']] += array($param['name'] => $param['value']);
                }
            }
        }
        foreach ($items as &$item) {
            #data holders for plugin events handlers
            $item['plugins'] = array(
                'before'           => array(),
                'after'            => array(),
                'post_title'       => array(),
                'post_title_right' => array(),
            );

            if (isset($item['blog_id'])) {
                $blog_id = $item['blog_id'];
                if (isset($extend_data['blog']) && isset($extend_data['blog'][$blog_id])) {
                    $blog = $extend_data['blog'][$blog_id];
                    if (isset($blog['url'])) {
                        $item['blog_url'] = $blog['url'];
                    }
                    $item['icon'] = isset($blog['icon_html']) ? $blog['icon_html'] : '';
                    $item['color'] = isset($blog['color']) ? $blog['color'] : '';
                    if (isset($blog['status'])) {
                        $item['blog_status'] = $blog['status'];
                    }
                    if (isset($blog['url'])) {
                        $item['blog_url'] = $blog['url'];
                    }
                    if (isset($blog['name'])) {
                        $item['blog_name'] = $blog['name'];
                    }
                } else {
                    $item['color'] = '';
                    $item['icon'] = '';
                }
            }

            if (isset($item['comment_count'])) {
                /**
                 * Backward compatibility with older themes
                 * @deprecated
                 */
                $item['comment_str_translate'] = _w('comment', 'comments', $item['comment_count']);
            }

            if (!isset($extend_options['link']) || $extend_options['link']) {
                if (isset($item['blog_status']) && ($item['blog_status'] != blogBlogModel::STATUS_PUBLIC)) {
                    $item['link'] = false;
                } else {
                    $item['link'] = blogPost::getUrl($item);
                }
            }

            if (empty($item['title'])) {
                $item['title'] = _w("(empty title)");
            }

            if (!empty($extend_options['escape'])) {
                $item['title'] = htmlspecialchars($item['title'], ENT_QUOTES, 'utf-8');
                $item['user']['name'] = htmlspecialchars($item['user']['name'], ENT_QUOTES, 'utf-8');
            }


            unset($item);
        }
        if (!isset($extend_options['plugin']) || $extend_options['plugin']) {

            /**
             * Prepare post data
             * Extend each post item via plugins data
             * @event prepare_posts_frontend
             * @event prepare_posts_backend
             * @example public function preparePost(&$items)
             * {
             *     foreach ($items as &$item) {
             *         $item['post_title'][$this->id] = 'Extra post title html code here';
             *     }
             * }
             * @param array[int][string]mixed $items Post items
             * @param array[int][string]mixed $items[id] Post item
             * @param array[int][string]int $items[id]['id'] Post item ID
             * @param array[int][string][string]string $items[id]['before'][%plugin_id%] Placeholder for plugin %plugin_id% output
             * @param array[int][string][string]string $items[id]['after'][%plugin_id%] Placeholder for plugin %plugin_id% output
             * @param array[int][string][string]string $items[id]['post_title'][%plugin_id%] Placeholder for plugin %plugin_id% output
             * @param array[int][string][string]string $items[id]['post_title_right'][%plugin_id%] Placeholder for plugin %plugin_id% output
             * @return void
             */
            wa()->event('prepare_posts_'.wa()->getEnv(), $items);
        }
        return $items;
    }

    /**
     * Get post entry by it slug
     * @param string $slug
     * @return array
     */
    public function getBySlug($slug)
    {
        $where = "(url = s:slug)";
        return $this->select('*')->where($where, array('slug' => $slug))->fetch();
    }

    /**
     *
     * Update blog post item
     * @param int $id
     * @param array $data
     * @param array $current_data
     * @throws waException
     * @return int post id
     */
    public function updateItem($id, $data = array(), $current_data = array())
    {
        $plugin = array();
        $contact_id = wa()->getUser()->getId();
        foreach ($data as $field => $value) {
            if (!isset($this->fields[$field]) || ($field == $this->id)) {
                if (isset($data['plugin'])) {
                    $plugin = $data['plugin'];
                }
                unset($data[$field]);
            }
        }

        if ($id) {
            if (!$current_data) {
                $current_data = $this->getByField(array($this->id => $id));
                if (!$current_data) {
                    throw new waException(_w('Post not found'), 404);
                }
            }

            if (!$contact_id) { //use author id for cron task
                $contact_id = $current_data['contact_id'];
            }
        } else {
            $current_data = array();
            if (empty($data['contact_id'])) {
                $data['contact_id'] = $contact_id;
            } else {
                blogHelper::checkRights($data['blog_id'], $contact_id, ($contact_id != $data['contact_id']) ? blogRightConfig::RIGHT_FULL : blogRightConfig::RIGHT_READ_WRITE);
            }
        }

        //check rights for non admin
        $source_data = array(
            'contact_id' => isset($current_data['contact_id']) ? $current_data['contact_id'] : $data['contact_id'],
            'blog_id'    => isset($current_data['blog_id']) ? $current_data['blog_id'] : $data['blog_id'],
        );
        $target_data = array(
            'contact_id' => isset($data['contact_id']) ? $data['contact_id'] : $source_data['contact_id'],
            'blog_id'    => isset($data['blog_id']) ? $data['blog_id'] : $source_data['blog_id'],
        );

        //check editor rights
        blogHelper::checkRights($source_data['blog_id'], $contact_id);

        //change blog
        if ($source_data['blog_id'] != $target_data['blog_id']) {
            //check editor rights for target blog
            blogHelper::checkRights($target_data['blog_id'], $contact_id, ($contact_id != $target_data['contact_id']) ? blogRightConfig::RIGHT_FULL : blogRightConfig::RIGHT_READ_WRITE);

            //check (new) author rights
            if ($contact_id != $target_data['contact_id']) {
                //skip it = for admin it allowed
                //blogHelper::checkRights($target_data['blog_id'],$target_data['contact_id']);
            }
        } else {
            //check new author rights
            if (($contact_id != $target_data['contact_id']) && ($target_data['contact_id'] != $source_data['contact_id'])) {
                blogHelper::checkRights($target_data['blog_id'], $target_data['contact_id']);
            }
        }

        //status changes
        if (isset($data['status'])) {
            switch ($data['status']) {
                case self::STATUS_PUBLISHED:
                    if (!isset($data['datetime']) || !$data['datetime']) {
                        if (!isset($current_data['datetime']) || !$current_data['datetime']) {
                            $data['datetime'] = date("Y-m-d H:i:s");
                        } elseif (isset($current_data['status']) && !in_array($current_data['status'], array(self::STATUS_PUBLISHED, self::STATUS_SCHEDULED))) {
                            $data['datetime'] = date("Y-m-d H:i:s");
                        } else {
                            unset($data['datetime']);
                        }
                    }
                    break;
                case self::STATUS_DRAFT:
                    if (!isset($data['datetime']) || !$data['datetime']) {
                        if (!isset($current_data['datetime']) || !$current_data['datetime']) {
                            $data['datetime'] = date("Y-m-d H:i:s");
                        } else {
                            unset($data['datetime']);
                        }
                    }
                    break;
                case self::STATUS_SCHEDULED:
                    if (!isset($data['datetime']) || !$data['datetime']) {
                        unset($data['datetime']);
                    }
                    break;
                case self::STATUS_DEADLINE:
                    if (!isset($data['datetime']) || !$data['datetime'] || (is_array($data['datetime']) && !$data['datetime'][0])) {
                        $data['status'] = self::STATUS_DRAFT;
                        $data['datetime'] = date("Y-m-d H:i:s");
                    }

                    break;
            }
        }

        if (!$id && (!isset($data['contact_id']) || !$data['contact_id'])) {
            $data['contact_id'] = wa()->getUser()->getId();
        }

        if (isset($data['url']) && strlen($data['url'])) {
            if (substr($data['url'], -1) == '/') {
                $data['url'] = preg_replace('~\/+$~', '', $data['url']);
            }
            if (strpos($data['url'], '/') !== false) {
                throw new waException(_w('URL must not contain /'));
            }
            if ($this->checkUrl($data['url'], $id)) {
                throw new  waException(_w('This address is already in use').' '.$data['url']);
            }
        } else {
            //$data['url'] = blogHelper::transliterate($data['url']);
        }

        $edit = $id ? true : false;

        $event_map = array(
            0                      => array(
                0                      => array('post_presave', 'post_save'),
                self::STATUS_PUBLISHED => array('post_prepublish', 'post_publish'),
                self::STATUS_SCHEDULED => array('post_preshedule', 'post_shedule'),
                self::STATUS_DEADLINE  => array('post_presave', 'post_save'),
                self::STATUS_DRAFT     => array('post_presave', 'post_save'),
            ),
            self::STATUS_DRAFT     => array(
                0                      => array('post_presave', 'post_save'),
                self::STATUS_PUBLISHED => array('post_prepublish', 'post_publish'),
                self::STATUS_SCHEDULED => array('post_preshedule', 'post_shedule'),
                self::STATUS_DEADLINE  => array('post_presave', 'post_save'),
                self::STATUS_DRAFT     => array('post_presave', 'post_save'),
            ),
            self::STATUS_DEADLINE  => array(
                0                      => array('post_presave', 'post_save'),
                self::STATUS_PUBLISHED => array('post_prepublish', 'post_publish'),
                self::STATUS_SCHEDULED => array('post_preshedule', 'post_shedule'),
                self::STATUS_DEADLINE  => array('post_presave', 'post_save'),
                self::STATUS_DRAFT     => array('post_presave', 'post_save'),
            ),
            self::STATUS_SCHEDULED => array(
                0                      => array('post_presave', 'post_save'),
                self::STATUS_PUBLISHED => array('post_prepublish', 'post_publish'),
                self::STATUS_SCHEDULED => array('post_presave', 'post_save'),
                self::STATUS_DEADLINE  => array('post_presave', 'post_save'),
                self::STATUS_DRAFT     => array('post_presave', 'post_save'),
            ),
            self::STATUS_PUBLISHED => array(
                0                      => array('post_presave', 'post_save'),
                self::STATUS_PUBLISHED => array('post_presave', 'post_save'),
                self::STATUS_SCHEDULED => array('post_preshedule', 'post_shedule'),
                self::STATUS_DEADLINE  => array('post_presave', 'post_save'),
                self::STATUS_DRAFT     => array('post_presave', 'post_save'),
            ),
        );

        $events = $event_map[isset($current_data['status']) ? $current_data['status'] : 0][isset($data['status']) ? $data['status'] : 0];
        $data['plugin'] = $plugin;


        /**
         * @event post_prepublish
         * @event post_preshedule
         * @event post_presave
         * @param array[string]mixed $data
         * @param array[string]int $data['id']
         * @param array[string][string]mixed $data['plugin']['%plugin_id']
         * @return array[%plugin_id%][%field%]string Error message for field %field%
         */
        $errors = wa()->event(array_shift($events), $data);

        if ($id) {
            if ($source_data['blog_id'] != $target_data['blog_id']) {
                $comment_model = new blogCommentModel();
                $comment_model->updateByField('post_id', $id, array('blog_id' => $target_data['blog_id']));
            }
            $this->updateById($id, $data);
            $data[$this->id] = $id;
        } else {
            $id = $this->insert($data);
            blogActivity::setUserActivity();
            $data[$this->id] = $id;
            if (!isset($data['url']) || strlen($data['url']) == 0) {
                $this->updateById($id, array('url' => $id));
            }
        }


        //status changed
        //blog_id changed
        $data = array_merge($current_data, $data);
        $blog_model = new blogBlogModel();

        if ($edit) {
            //unpublish
            if ($current_data['status'] == self::STATUS_PUBLISHED && $data['status'] != self::STATUS_PUBLISHED) {
                $blog_model->updateQty($data['blog_id'], '-1');

                //publish
            } elseif ($current_data['status'] != self::STATUS_PUBLISHED && $data['status'] == self::STATUS_PUBLISHED) {
                $blog_model->updateQty($data['blog_id'], '+1');
                //move
            } elseif (isset($current_data['blog_id']) && $current_data['status'] == self::STATUS_PUBLISHED && $data['status'] == self::STATUS_PUBLISHED && ($current_data['blog_id'] != $data['blog_id'])) {
                $blog_model->updateQty($data['blog_id'], '+1');
                $blog_model->updateQty($current_data['blog_id'], '-1');
            }

        } else {
            if ($data['status'] == self::STATUS_PUBLISHED) {
                $blog_model->updateQty($data['blog_id'], '+1');
            }
        }

        /**
         * @event post_publish
         * @event post_shedule
         * @event post_save
         * @param array[string]mixed $data
         * @param array[string]int $data['id']
         * @param array[string][string]mixed $data['plugin']['%plugin_id']
         * @return void
         */
        wa()->event(array_shift($events), $data);

        return $id;
    }

    /**
     * Delete records from table and related data
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
        if ($post_ids = array_keys($items)) {
            /**
             * @event post_predelete
             * @param array[] int $post_ids array of post's ID
             * @return void
             */
            wa()->event('post_predelete', $post_ids);
            $res = parent::deleteByField('id', $post_ids);
            if ($res) {
                $comment_model = new blogCommentModel();
                $comment_model->deleteByField('post_id', $post_ids);

                $params_model = new blogPostParamsModel();
                $params_model->deleteByField('post_id', $post_ids);

                $blog_model = new blogBlogModel();
                $blogs = array();
                foreach ($items as $item) {
                    $blogs[] = $item['blog_id'];
                }
                $blogs = array_unique($blogs);
                $blog_model->recalculate($blogs);
                /**
                 * @event post_delete
                 * @param array[] int $post_ids array of post's ID
                 * @return void
                 */
                wa()->event('post_delete', $post_ids);
            }
        }
        return $res;
    }

    public function getAddedPostCount($datetime, $blogs = null, $get_posts = false)
    {
        $where = array();
        $where[] = $this->getWhereByField('status', self::STATUS_PUBLISHED);
        if ($blogs !== null) {
            $where[] = $this->getWhereByField('blog_id', $blogs);
        }
        $where[] = 'contact_id != '.$this->escape(wa()->getUser()->getId());
        $where[] = 'datetime > s:datetime';
        $where = '('.implode(') AND (', $where).')';

        if ($get_posts) {
            $sql = "SELECT blog_id, {$this->id} FROM {$this->table} WHERE {$where}";
            $rows = $this->query($sql, array('datetime' => $datetime))->fetchAll();
            $result = array();
            foreach ($rows as $row) {
                $result[$row['blog_id']][] = $row[$this->id];
            }
            foreach ($result as $blog_id => $posts) {
                $result[$blog_id] = implode(':', $posts);
            }
            return $result;
        } else {
            $sql = "SELECT blog_id, COUNT({$this->id}) FROM {$this->table}
                    WHERE {$where} GROUP BY blog_id";
            return $this->query($sql, array('datetime' => $datetime))->fetchAll('blog_id', true);
        }
    }

    static public function getPureUrls($post)
    {
        if (isset($post['url'])) {
            unset($post['url']);
        }

        $urls = blogPost::getUrl($post);

        $replace = array_merge(
            explode(' ', date('Y n j')),
            (array)''
        );
        $urls = str_replace(array('%year%', '%month%', '%day%', '%post_url%/'), $replace, $urls);

        return $urls;
    }

    static public function getPreviewHash($options, $regen_if_expired = true)
    {
        $app = wa()->getApp();
        $app_settings_model = new waAppSettingsModel();
        // preview_hash is the salt that takes into account live-time
        $hash = $app_settings_model->get($app, 'preview_hash');
        if ($hash) {
            $hash_parts = explode('.', $hash);
            if ($regen_if_expired && time() - $hash_parts[1] > 14400) {
                $hash = '';
            }
        }

        if (!$hash) {
            $hash = uniqid().'.'.time();
            $app_settings_model->set($app, 'preview_hash', $hash);
        }

        $options += array(
            'contact_id' => '',
            'blog_id'    => '',
            'post_id'    => '',
            'user_id'    => '',
        );

        $hash = md5($hash.$options['contact_id'].$options['blog_id'].$options['post_id'].$options['user_id']);

        return $hash;
    }

    /**
     * Validate data
     *
     * @param array &$data
     * @param array $options
     *
     * @return array messages or empty array
     */
    public function validate(&$data, $options = array())
    {
        $messages = array();

        if ($data['blog_status'] != blogBlogModel::STATUS_PRIVATE) {

            if (!empty($data['id'])) {
                $url_validator = new blogSlugValidator(array(
                    'id' => $data['id']
                ));
            } else {
                if (isset($options['transliterate']) && $options['transliterate'] && !$data['url'] && $data['title']) {
                    $data['url'] = blogHelper::transliterate($data['title']);
                }
                $url_validator = new blogSlugValidator();
            }

            $url_validator->setSubject(blogSlugValidator::SUBJECT_POST);

            if (!$url_validator->isValid($data['url'])) {

                $messages['url'] = current($url_validator->getErrors());

                if ($url_validator->isError(blogSlugValidator::ERROR_REQUIRED) &&
                    ($data['id'] || (!$data['id'] && $data['status'] == blogPostModel::STATUS_DRAFT))
                ) {

                    $url = $this->select('url')->where('id = i:id', array('id' => $data['id']))->fetchField('url');
                    $data['url'] = $url ? $url : $this->genUniqueUrl($data['title']);

                    unset($messages['url']);
                    if (!$url_validator->isValid($data['url'])) {
                        $messages['url'] = current($url_validator->getErrors());
                    }
                } elseif (!empty($options['make'])) {
                    $data['url'] = $this->genUniqueUrl($data['url']);

                    unset($messages['url']);
                    if (!$url_validator->isValid($data['url'])) {
                        $messages['url'] = current($url_validator->getErrors());
                    }
                }
            }

        } else {

            if (empty($data['id'])) {
                $data['url'] = $this->genUniqueUrl(empty($data['url']) ? $data['title'] : $data['url']);
            } else {
                $url = $this->select('url')->where('id = i:id', array('id' => $data['id']))->fetchField('url');
                $data['url'] = $url ? $url : $this->genUniqueUrl($data['title']);
            }

        }

        if (isset($data['datetime']) && !is_null($data['datetime'])) {


            if (!empty($options['datetime'])) {
                $formats = (array)$options['datetime'];
            } elseif (isset($options['datetime'])) {
                $formats = array();
            } elseif (strpos($data['datetime'], ':') !== false) {
                $formats = array('fulldatetime', 'datetime');
            } else {
                $formats = array('date');
            }

            if ($data['datetime'] != '') {
                $datetime = $data['datetime'];
                foreach ($formats as $format) {
                    try {
                        if ($datetime = waDateTime::parse($format, $data['datetime'])) {
                            break;
                        }

                    } catch (Exception $ex) {
                        $messages['datetime'] = _w('Incorrect format');
                        waLog::log($ex->getMessage());
                    }

                }
                if (preg_match('/^([\d]{4})\-([\d]{1,2})\-([\d]{1,2})(\s|$)/', $datetime, $matches)) {
                    if (!checkdate($matches[2], $matches[3], $matches[1])) {
                        $messages['datetime'] = _w('Incorrect format');
                    }
                }
                $data['datetime'] = $datetime;
            } else if ($data['status'] != blogPostModel::STATUS_DRAFT) {
                $data['datetime'] = false;
            }
            if ($data['datetime'] === false) {
                $messages['datetime'] = _w('Incorrect format');
            }
        }
        /**
         * @event post_validate
         * @param array[string]mixed $data
         * @param array['plugin']['%plugin_id%']mixed plugin data
         * @return array['%plugin_id%']['field']string error
         */
        $messages['plugin'] = wa()->event('post_validate', $data);
        if (empty($messages['plugin'])) {
            unset($messages['plugin']);
        }

        return $messages;
    }

    public function getById($id, $exclude_fields = null)
    {
        if (!$exclude_fields) {
            return parent::getById($id);
        }
        foreach ((array)$exclude_fields as $name) {
            if (isset($this->fields[$name])) {
                unset($this->fields[$name]);
            }
        }
        $fields = array_keys($this->fields);
        $sql = "SELECT ".implode(',', $fields)." FROM ".$this->table;
        if ($where = $this->getWhereByField('id', $id)) {
            $sql .= " WHERE ".$where;
        }
        return $this->query($sql)->fetchAssoc();
    }

    public function getFieldsById($id, $fields = null)
    {
        $fields = (array)$fields;
        $sql = "SELECT ".implode(',', $fields)." FROM ".$this->table;
        if ($where = $this->getWhereByField('id', $id)) {
            $sql .= " WHERE ".$where;
        }
        return $this->query($sql)->fetchAssoc();
    }
}