<?php
class blogCategoryModel extends waModel
{
    protected $table = 'blog_category';

    public function get($ids = array())
    {
        return $this->select('*')->order('sort')->fetchAll('id');
    }

    /**
     *
     * Get post(s) categories
     * @param int|array $post_id
     */
    public function getByPost($post_id) {
        $sql = <<<SQL
        SELECT category.id as id, name, icon, post_id
		FROM {$this->table} AS category
		LEFT JOIN blog_post_category AS post_category
		ON (post_category.category_id = category.id)
		WHERE post_id IN(i:post_id)
SQL;
        return $this->query($sql,array('post_id'=>$post_id))->fetchAll(is_array($post_id)?null:'id');
    }

    public function sort($id, $sort)
    {
        $entry = $this->getById($id);
        if ($entry) {

            $this->query("UPDATE {$this->table} SET sort = sort - 1 WHERE sort >= i:sort",
            array('sort'=>$entry['sort'],'max_sort'=>$sort));
            $this->query("UPDATE {$this->table} SET sort = sort + 1 WHERE sort >= i:sort",
            array('sort'=>$sort));

            $this->updateById($id, array('sort'=>$sort));
        }
    }

    public function recalculate($ids = array())
    {

        $sql = <<<SQL
		UPDATE {$this->table}
		SET qty = (
			SELECT COUNT(blog_post_category.post_id)
			FROM blog_post_category
			JOIN blog_post ON (blog_post_category.post_id = blog_post.id)
			WHERE
				blog_post_category.category_id = {$this->table}.id
			AND
				blog_post.status = s:status
		)
SQL;
        if ($ids) {
            $sql .= " WHERE {$this->table}.id IN (:ids)";
        }
        $this->query($sql,array('ids'=>(array)$ids,'status'=>blogPostModel::STATUS_PUBLISHED));
    }

    public function deleteByField($field, $value = null)
    {
        $post_category_model = new blogCategoryPostModel();
        if($field == $this->id) {
            $ids = $value;
        } else {
            $ids = $this->select($this->id)->where($this->getWhereByField($field,$value))->fetchAll();
        }
        $post_category_model->deleteByField('category_id',$ids);
        parent::deleteByField($field, $value);
    }

    private function genUniqueUrl($from,$id = null)
    {
        static $time = 0;
        static $counter = 0;
        $from = preg_replace('/\s+/', '-', $from);
        $url = blogHelper::transliterate($from);
        $field_length = 255;//$this->fields['url']['length']


        if (strlen($url) == 0) {
            $url = self::shortUuid();
        } else {
            $url = mb_substr($url, 0, $field_length);
        }
        $url = mb_strtolower($url);
        $where = '';
        if ($id) {
            $where = " AND NOT ({$this->getWhereByField($this->id,$id)})";
        }

        $pattern = mb_substr($this->escape($url, 'like'),0, $field_length-3). '%';
        $sql = "SELECT url FROM {$this->table} WHERE url LIKE '{$pattern}'{$where} ORDER BY LENGTH(url)";

        $alike = $this->query($sql)->fetchAll('url');

        if (is_array($alike) && isset($alike[$url])) {
            $last = array_shift($alike);
            $counter = 1;
            do {
                $modifier = "-{$counter}";
                $length  = mb_strlen($modifier);
                $url = mb_substr($last['url'], 0, $field_length - $length).$modifier;
            } while ( ($counter++ < 99) && isset($alike[$url]));
            if (isset($alike[$url])) {
                $short_uuid = self::shortUuid();
                $length  = mb_strlen($short_uuid);

                $url = mb_substr($last['url'], 0, $field_length - $length).$short_uuid;
            }
        }

        return mb_strtolower($url);
    }

    public function updateByField($field, $value, $data = null, $options = null, $return_object = false)
    {
        if(isset($data['url'])) {
            if($field == $this->id) {
                $id = $value;
            } else {
                $id = $this->select($this->id)->where($this->getWhereByField($field,$value))->fetchField($this->id);
            }
            $data['url'] = $this->genUniqueUrl(empty($data['url'])?$data['name']:$data['url'],$id);
        }
        return parent::updateByField($field, $value, $data, $options, $return_object);
    }

    public function insert($data, $type = 0)
    {

        $data['url'] = $this->genUniqueUrl(empty($data['url'])?$data['name']:$data['url']);
        return parent::insert($data, $type);
    }

}