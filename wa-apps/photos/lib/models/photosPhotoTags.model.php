<?php

class photosPhotoTagsModel extends waModel
{
    protected $table = 'photos_photo_tags';

    /**
     *
     * Assign tags to photos. Tags just assign to photos (without removing if exist for concrete photo)
     * @param array|int $photo_ids
     * @param array|int $tag_ids
     * @throws Exception
     */
    public function assign($photo_ids, $tag_ids = array())
    {
        if (!$photo_ids) {
            throw new Exception("Can't assign tags: unkown any photo id");
        }

        $photo_ids = (array) $photo_ids;

        $photo_model = new photosPhotoModel();

        $where = $photo_model->getWhereByField('id', $photo_ids);
        // define if we have parents for any given photo
        $parents = $photo_model->select('parent_id')->where($where . ' AND parent_id != 0')->fetchAll('parent_id', true);
        // we have parents - we have stacks
        if ($parents) {
            $photo_ids = array_merge($photo_ids, array_keys($parents));
        }

        $where = $photo_model->getWhereByField('parent_id', $photo_ids);
        // get children for every stack
        $children = $photo_model->select('id')->where($where)->fetchAll('id', true);
        if ($children) {
            $photo_ids = array_merge($photo_ids, array_keys($children));
        }
        // remove doubles
        $photo_ids = array_unique($photo_ids);

        $tag_ids = (array) $tag_ids;

        $sql = "SELECT * FROM {$this->table} ";
        if ($where = $this->getWhereByField('photo_id', $photo_ids)) {
            $sql .= " WHERE $where";
        }
        $existed_tags = array();
        foreach ($this->query($sql) as $item) {
            $existed_tags[$item['photo_id']][$item['tag_id']] = true;
        }

        foreach ($tag_ids as $tag_id) {
            $add = array();
            foreach ($photo_ids as $photo_id) {
                if (!isset($existed_tags[$photo_id][$tag_id])) {
                    $add[] = array(
                        'photo_id' => $photo_id,
                        'tag_id' => $tag_id
                    );
                }
            }
            if (!empty($add)) {
                if ($this->multiInsert($add)) {
                    $added_count = count($add);
                    $this->query("UPDATE photos_tag SET count = count + $added_count WHERE id = i:id",
                        array('id' => $tag_id)
                    );
                }
            }
        }
    }


    public function delete($photo_ids, $tag_ids)
    {
        // delete tags
        $this->deleteByField(array('photo_id' => $photo_ids, 'tag_id' => $tag_ids));
        // decrease count for tags
        $tag_model = new photosTagModel();
        $tag_model->decreaseCounters($tag_ids, count($photo_ids));
    }


    /**
    * Set tags to this photo. If tag doesn't exist it will be created.
    * If photo hasn't tag anymore it will be removed for this photo.
    * Take into account stack of photos
    *
    * @param int $photo_id
    * @param array $tags NAMES of tags
    * @throws Exception
    */
    public function set($photo_id, $tags = array())
    {
        if (!$photo_id) {
            throw new Exception("Can't set tags: unkown photo id");
        }
        $photo_model = new photosPhotoModel();
        $photo = $photo_model->select('id, parent_id, stack_count')->where('id = i:photo_id', array('photo_id' => $photo_id))->fetch();
        // we have photo in stack
        if ($photo['parent_id'] != 0) {
            $photo_id = $photo['parent_id'];
        }
        $children = $photo_model->select('id')->where('parent_id = i:photo_id', array('photo_id' => $photo_id))->fetchAll('id', true);
        if ($children) {     // we have children in stack
            $photo_id = array_merge((array)$photo_id, array_keys($children));
        } else {    // we have just photo
            $photo_id = (array)$photo_id;
        }

        $tag_model = new photosTagModel();
        $tag_ids = $tag_model->getIds($tags, true);
        foreach ($photo_id as $id) {
            $this->_set($id, $tag_ids);
        }
    }

    private function _set($photo_id, $tag_ids = array())
    {
        $tag_model = new photosTagModel();
        $delete_photo_tags = $this->getByField('photo_id', $photo_id, 'tag_id');

        foreach ($tag_ids as $tag_id) {
            if (!isset($delete_photo_tags[$tag_id])) {
                $this->insert(
                    array(
                        'photo_id' => $photo_id,
                        'tag_id' => $tag_id
                    )
                );
                $tag_model->query('UPDATE ' . $tag_model->getTableName() . ' SET count = count + 1 WHERE id = i:id', array('id' => $tag_id));
            } else {
                unset($delete_photo_tags[$tag_id]);
            }
        }
        $delete_tag_ids = array_keys($delete_photo_tags);
        $this->deleteByField(
            array(
                'tag_id' => $delete_tag_ids,
                'photo_id' => $photo_id
            )
        );
        $tag_model->decreaseCounters($delete_tag_ids);
        return true;
    }

    public function getTags($photo_id)
    {
        $sql = "SELECT t.id, t.name, pt.photo_id FROM ".$this->table." pt JOIN photos_tag t ON pt.tag_id = t.id";
        if ($where = $this->getWhereByField('photo_id', $photo_id)) {
            $sql .= " WHERE $where";
        }
        $data = $this->query($sql);
        $result = array();
        foreach($data as $row) {
            $result[$row['photo_id']][$row['id']] = $row['name'];
        }
        if (!is_array($photo_id) && !empty($result)) {
            $result = $result[$photo_id];
        }
        return $result;
    }

    public function getTagIds($photo_id)
    {
        $tags = $this->getByField('photo_id', $photo_id, 'tag_id');

        return array_keys($tags);
    }
}