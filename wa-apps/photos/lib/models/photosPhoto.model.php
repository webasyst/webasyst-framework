<?php

class photosPhotoModel extends waModel
{
    protected $table = 'photos_photo';

    /**
     * Delete by id with taking into account case of stack.
     *
     * If deleting photo is single photo then just delete photo.
     * If deleting photo is parent of stack then first of all make unstack
     * If deleting photo is children photo in stack then after deleting photo decrease stack
     *
     * @param int $id
     */
    public function delete($id)
    {
        $id = (int) $id;
        if (!$id) {
            return;
        }

        $parent = $this->getStackParent($id);
        if ($parent && $parent['id'] == $id) {
            $this->unstack($id);
        }

        // first of all try delete from disk
        $photo = $this->getById($id);
        $path = photosPhoto::getPhotoPath($photo);
        $thumb_dir = photosPhoto::getPhotoThumbDir($photo);
        waFiles::delete(dirname($path));
        waFiles::delete($thumb_dir);

        // delete some related models
        $related_models = array('AlbumPhotos', 'PhotoExif', 'PhotoRights');
        foreach ($related_models as $name) {
            $model_name = 'photos'.$name.'Model';
            $model = new $model_name;
            $model->deleteByField('photo_id', $id);
        }

        // especial deleting rest models:

        //  tags
        $photo_tags_model = new photosPhotoTagsModel();
        $tags_model = new photosTagModel();
        $tag_ids = array_keys($photo_tags_model->getByField('photo_id', $id, 'tag_id'));
        $photo_tags_model->deleteByField('photo_id', $id);
        $tags_model->decreaseCounters($tag_ids);

        // delete photo(s) itself
        $this->deleteById($id);

        // if deleted just one photo in stack (not stack itself)
        if ($parent && $parent['id'] != $id) {
            $stack_count = $parent['stack_count'] - 1;
            $stack_count = $stack_count > 1 ? $stack_count : 0;
            $sql = "UPDATE {$this->table} SET stack_count = i:stack_count WHERE id = i:id";
            $this->exec($sql, array(
                'id' => $parent['id'],
                'stack_count' => $stack_count
            ));
        }
    }

    public function getByParent($id)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE id = i:id OR parent_id = i:id
                ORDER BY sort";
        return $this->query($sql, array('id' => $id))->fetchAll();
    }

    public function getIdsByParent($parent_id)
    {
        $sql = "SELECT id FROM ".$this->table."
                WHERE id = i:id OR parent_id = i:id
                ORDER BY sort";
        return array_keys($this->query($sql, array('id' => $parent_id))->fetchAll('id'));
    }

    public function makeStack($parent_id, $photo_ids)
    {
        // first of all unstack if needed
        $parent = $this->getById($parent_id);
        if ($parent['stack_count'] > 0) {
            $this->unstack($parent_id);
        }
        $this->_makeStack($parent_id, $photo_ids);
    }

    public function appendToStack($parent_id, $photo_ids)
    {
        $this->_makeStack($parent_id, $photo_ids, 'append');
    }

    private function _makeStack($parent_id, $photo_ids, $op = 'make')
    {
        $photo_ids = (array) $photo_ids;

        if ($op == 'make') {
            $where = $this->getWhereByField('id', $photo_ids);

            // get description - first not empty description but description of parent is first-priority
            $sql = "SELECT description FROM {$this->table} WHERE id = i:parent_id AND description IS NOT NULL
                    UNION
                    SELECT description FROM {$this->table} WHERE $where AND description IS NOT NULL LIMIT 0,1";
            $description = $this->query($sql, array(
                'parent_id' => $parent_id
            ))->fetchField('description');

            // get max rate of all photos
            $sql = "SELECT MAX(rate) rate FROM {$this->table} WHERE id = i:parent_id OR $where";
            $rate = $this->query($sql, array(
                'parent_id' => $parent_id
            ))->fetchField('rate');

            // get status
            $sql = "SELECT status FROM {$this->table} WHERE id = i:parent_id";
            $status = $this->query($sql, array(
                'parent_id' => $parent_id
            ))->fetchField('status');

            $stack_count = 1;
            $sort = 1;

        } else {
            $parent = $this->getById($parent_id);

            $rate =        $parent['rate'];
            $description = $parent['description'];
            $status =      $parent['status'];
            $stack_count = $parent['stack_count'];

            // get last sort value of stack plus 1
            $sql = "SELECT sort FROM {$this->table} WHERE parent_id = i:parent_id ORDER BY sort DESC LIMIT 1";
            $sort = $this->query($sql, array(
                'parent_id' => $parent_id
            ))->fetchField('sort') + 1;
        }

        // get groups
        $photo_rights_model = new photosPhotoRightsModel();
        $groups = array_keys($photo_rights_model->getByField('photo_id', $parent_id, 'group_id'));

        // make first of all operations connected with file-manipulations
        foreach ($photo_ids as $id) {
            // update access
            $this->upAccess($id, array(
                'status' => $status,
                'groups' => $groups
            ));
        }

        // make children of stack
        foreach ($photo_ids as $id) {
            $this->updateById($id, array(
                'parent_id' => $parent_id,
                'description' => $description,
                'rate' => $rate,
                'sort' => $sort++,
                'stack_count' => 0
            ));
        }

        // make parent of stack
        $this->updateById($parent_id, array(
            'parent_id' => 0,
            'description' => $description,
            'rate' => $rate,
            'stack_count' => $stack_count + count($photo_ids),
            'sort' => 0
        ));

        if ($op == 'make') {
            $photo_ids[] = $parent_id;
        } else {
            $photo_ids = array_keys($this->getByField('parent_id', $parent_id, 'id'));
            $photo_ids[] = $parent_id;
        }

        // merge tags for stack
        $photo_tags_model = new photosPhotoTagsModel();
        $tag_ids = array_keys($photo_tags_model->getByField('photo_id', $photo_ids, 'tag_id'));
        $photo_tags_model->assign($photo_ids, $tag_ids);

        // merge albums for stack
        $album_photos_model = new photosAlbumPhotosModel();
        $album_ids = array_keys($album_photos_model->getByField('photo_id', $photo_ids, 'album_id'));
        $album_photos_model->add($photo_ids, $album_ids, false);
    }

    public function unstack($id)
    {
        $sql = "UPDATE {$this->table} SET sort = 0, parent_id = 0, stack_count = 0
                WHERE (parent_id = i:parent_id OR id = i:parent_id)";
        $this->exec($sql, array(
            'parent_id' => $id,
        ));
    }

    /**
     * Update by id with taking into account case of stack
     *
     * @param int|array $id
     * @param array $photo
     */
    public function update($id, $photo)
    {
        $id = (array) $id;
        $where = $this->getWhereByField('id', $id);
        // define if we have parents for any given photo
        $parents = $this->select('parent_id')->where($where . ' AND parent_id != 0')->fetchAll('parent_id', true);
        // we have parents - we have stacks
        if ($parents) {
            $id = array_merge($id, array_keys($parents));
        }

        $where = $this->getWhereByField('parent_id', $id);
        // get children for every stack
        $children = $this->select('id')->where($where)->fetchAll('id', true);
        if ($children) {
            $id = array_merge($id, array_keys($children));
        }
        // remove doubles
        $id = array_unique($id);
        $this->updateById($id, $photo);
    }

    public function moveStackSort($id, $before_id)
    {
        $parent_id = $this->select('parent_id')->where('id = i:id', array(
            'id' => $id
        ))->fetchField('parent_id');
        $parent_id = $parent_id ? $parent_id : $id;

        if ($before_id) {
            $sql = "SELECT sort FROM {$this->table} WHERE id = i:before_id";
            $sort = $this->query($sql, array(
                'before_id' => $before_id
            ))->fetchField('sort');
            $sql = "UPDATE {$this->table} SET sort = sort + 1
                    WHERE (parent_id = i:parent_id OR id = i:parent_id) AND sort >= i:sort";
            $this->exec($sql, array(
                'parent_id' => $parent_id,
                'sort' => $sort
            ));
        } else {
            $sql = "SELECT sort FROM {$this->table} WHERE (parent_id = i:parent_id OR id = i:parent_id) ORDER BY sort DESC LIMIT 0, 1";
            $sort = $this->query($sql, array(
                'parent_id' => $parent_id
            ))->fetchField('sort') + 1;
        }

        $this->updateById($id, array(
            'sort' => $sort
        ));
        $first_id = $this->select('id')
            ->where('parent_id = i:parent_id OR id = i:parent_id', array(
                'parent_id' => $parent_id
            ))
            ->order('sort')
            ->limit(1)
            ->fetchField('id');

        if ($id != $parent_id && $first_id == $id || $id == $parent_id && $first_id != $id) {
            $stack_count = $this->select('stack_count')->where('id = i:id', array(
                'id' => $parent_id
            ))->fetchField('stack_count');
            $sql = "UPDATE {$this->table} SET
                        parent_id = IF(id = i:new_parent_id, 0, i:new_parent_id),
                        stack_count = IF(id = i:new_parent_id, i:stack_count, 0)
                    WHERE parent_id = i:parent_id OR id = i:parent_id";
            $this->exec($sql, array(
                'new_parent_id' => $first_id,
                'stack_count' => $stack_count,
                'parent_id' => $parent_id
            ));
        }
    }

    public function getStackIds($photo)
    {
        $parent_id = $this->getStackParentId($photo);
        if ($parent_id) {
            return $this->getIdsByParent($parent_id);
        }
        return null;
    }

    /**
    * Get stack by id of contained photo.
    * Extend by the use $options key-value array
    *      boolean 'tags' get tags for each photo in stack
    *      mixed 'thumbs' get thumbs infos of photo if setted to true
    *
    * @param int $id
    * @param array $options
    */
    // TODO: getStack -> collection /stack/<id>
    public function getStack($id, $options = array())
    {
        $parent_id = $this->getStackParentId($id);
        if ($parent_id) {
            $stack = $this->getByParent($parent_id);
            if (!empty($options)) {
                $need_tags = isset($options['tags']) && $options['tags'];
                if ($need_tags) {
                    $photo_tags_model = new photosPhotoTagsModel();
                }
                foreach ($stack as &$s) {
                    if ($need_tags) {
                        $s['tags'] = $photo_tags_model->getTags($s['id']);
                    }
                    $s['thumb'] = photosPhoto::getThumbInfo($s, photosPhoto::getThumbPhotoSize());
                    $s['thumb_crop'] = photosPhoto::getThumbInfo($s, photosPhoto::getCropPhotoSize());
                    $s['thumb_big'] = photosPhoto::getThumbInfo($s, photosPhoto::getBigPhotoSize());
                    $s['thumb_middle'] = photosPhoto::getThumbInfo($s, photosPhoto::getMiddlePhotoSize());
                }
                unset($s);
            }
            return $stack;
        }
        return null;
    }

    /**
    * Get parent of stack if photo with $id constist in stack
    * @param array|int $photo photo or id of photo
    * @return int|bool
    */
    public function getStackParent($photo)
    {
        $parent_id = $this->getStackParentId($photo);
        return $parent_id ? $this->getById($parent_id) : false;
    }

    /**
     * Get parent_id of stack if photo is in stack
     * @param array|int $photo photo or id of photo
     * @return int|bool
     */
    public function getStackParentId($photo)
    {
        if (!is_array($photo)) {
            $id = (int)$photo;
            $photo = $this->select('id, parent_id, stack_count')->where('id = i:id', array(
                'id' => $id
            ))->fetch();
        }

        $parent_id = false;
        if ($photo['parent_id']) {
            $parent_id = $photo['parent_id'];
        } elseif ($photo['stack_count']) {
            $parent_id = $photo['id'];
        }
        return $parent_id;
    }

    public function getLastUploadedPhotoIds($datetime)
    {
        $sql = "SELECT id FROM {$this->table}";
        if ($datetime) {
            $sql .= " WHERE upload_datetime > '$datetime'";
        }
        return array_keys($this->query($sql)->fetchAll('id'));
    }

    public function countAll($datetime = null, $stack = false, $public_only = false)
    {
        $sql = "SELECT COUNT(id) FROM {$this->table}";

        $where = array();
        if ($datetime) {
            $where[] = "upload_datetime > '$datetime' AND contact_id != ".wa()->getUser()->getId();
        }
        if (!$stack) { // only parents
            $where[] = 'parent_id = 0';
        }
        if ($public_only) {
            $where[] = "status = 1";
        }
        if ($where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        return $this->query($sql)->fetchField();
    }

    public function countRated($datetime = null, $stack = false, $public_only = false)
    {
        $sql = "SELECT COUNT(id) FROM {$this->table} WHERE rate > 0";

        if ($datetime) {
            $sql .= " AND upload_datetime > '$datetime'";
        }
        if ($stack) {
            $sql .= ' AND parent_id = 0';
        }
        if ($public_only) {
            $sql .= ' AND status = 1 ';
        }
        return $this->query($sql)->fetchField();
    }

    public function updateAccess($photo_id, $status, $groups)
    {
        $photo_id = (array) $photo_id;
        foreach ($photo_id as $id) {
            $this->upAccess($id, array(
                'status' => $status,
                'groups' => $groups
            ));
        }
    }

    private function upAccess($photo_id, $data)
    {
        $status = $data['status'];
        $groups = $data['groups'];

        $photo = $this->getById($photo_id);
        if ($photo['status'] == $status && $status > 0) {
            return;
        }
        $old_path = photosPhoto::getPhotoPath($photo);
        $old_thumbs_dir = photosPhoto::getPhotoThumbDir($photo);

        $data = array('status' => $status);
        if ($status <= 0) {
            $data['hash'] = md5(uniqid(time(), true));
        } else {
            $data['hash'] = '';
        }
        $photo = $data + $photo;
        $path = photosPhoto::getPhotoPath($photo);
        if (!rename($old_path, $path)) {
            throw new waException('error');
        }
        waFiles::delete($old_thumbs_dir);   // strictly needed

        $this->updateById($photo_id, $data);
        $this->upRights($photo_id, $groups);
    }

    private function upRights($photo_id, $groups)
    {
        static $rights_model;
        $rights_model = $rights_model ? $rights_model : new photosPhotoRightsModel();

        // delete old rights
        $rights_model->deleteByField(array('photo_id' => $photo_id));
        // inser new rights
        $rights_model->multiInsert(array('photo_id' => $photo_id, 'group_id' => $groups));
    }

    /**
     * Get photos by id taking into account photos in stack (if $stack_childs is true) and
     * soring of input $photo_ids param (if $save_soring is true)
     *
     * @param int|array $photo_ids
     * @param bool $stack_childs
     * @param bool $save_sorting
     * @return array indexed by id
     */
    public function getPhotos($photo_ids, $stack_childs = true, $save_sorting = true)
    {
        $photo_ids = (array) $photo_ids;
        $original_photo_id = $photo_ids;

        if ($stack_childs && ($where = $this->getWhereByField('parent_id', $photo_ids))) {
            $sql = "SELECT id, parent_id FROM {$this->table} WHERE $where ORDER BY parent_id, sort";
            $result = $this->query($sql);
            foreach ($result as $item) {
                $photo_ids[] = $item['id'];
                $stacks[$item['parent_id']][] = $item['id'];
            }
            $stacks = array();    // for saving right sorting
            if ($save_sorting) {
                foreach ($result as $item) {
                    $stacks[$item['parent_id']][] = $item['id'];
                }
            }
        }
        $photo_ids = array_unique($photo_ids);
        $photos = $this->getByField('id', $photo_ids, 'id');

        if ($save_sorting) {
            $sorted_photos = array();
            foreach ($original_photo_id as $photo_id) {
                if (isset($stacks[$photo_id])) {
                    $sorted_photos[$photo_id] = $photos[$photo_id];
                    foreach ($stacks[$photo_id] as $stack_photo_id) {
                        $sorted_photos[$stack_photo_id] = $photos[$stack_photo_id];
                    }
                } else {
                    $sorted_photos[$photo_id] = $photos[$photo_id];
                }
            }
            return $sorted_photos;
        } else {
            return $photos;
        }
    }

    public function filterByField($photo_ids, $field, $value)
    {
        $where = $this->getWhereByField('id', $photo_ids);
        $where .= ' AND '.$this->getWhereByField($field, $value);
        $sql = "SELECT id FROM {$this->table} WHERE $where";
        return array_keys($this->query($sql)->fetchAll('id'));
    }

    public static function getPrivateUrl($photo)
    {
        return $photo['status'] <= 0 ? $photo['url'].':'.$photo['hash'] : null;
    }

    public static function getPrivateHash($photo)
    {
        return $photo['status'] <= 0 ? 'id/'.$photo['id'].':'.$photo['hash'] : null;
    }

    public static function parsePrivateUrl($url)
    {
        $parts = explode(':', $url);
        if (count($parts) == 2) {
            return $parts[1];
        }
        return null;
    }
}
