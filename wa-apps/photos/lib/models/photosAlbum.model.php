<?php

class photosAlbumModel extends waModel
{
    protected $table = 'photos_album';

    const TYPE_STATIC = 0;
    const TYPE_DYNAMIC = 1;

    /**
     * Add new album
     *
     * @param array|string $data If param is string that it means name
     * @param int $type If $data is string this param is ignored
     * @return int|boolean If successful that return id of new album else false
     */
    public function add($data, $type = 0)
    {
        if (!is_array($data)) {
            $data = array(
                'name' => $data,
                'type' => $type,
            );
        }
        $data['contact_id'] = waSystem::getInstance()->getUser()->getId();
        $data['create_datetime'] = date('Y-m-d H:i:s');
        $data['parent_id'] = 0;
        $data['sort'] = 0;

        // shift down albums in list
        $sql = "UPDATE {$this->table} SET sort = sort + 1 WHERE parent_id = 0";
        $this->query($sql);

        if (isset($data['url'])) {
            $url = $data['url'];
            unset($data['url']);
        }

        $id = $this->insert($data);
        if (isset($url)) {
            $this->updateUrl($id, $url);
        }
        return $id;
    }

    public function getByName($name)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE name LIKE '%".$this->escape($name, 'like')."%'";
        return $this->query($sql)->fetchAll();
    }
    /**
     *
     * @deprecated
     * @param boolean $public_only
     * @param boolean $owned_only
     */
    public function getStaticAlbums($public_only = false, $owned_only = false)
    {
        $user = wa()->getUser();
        $sql = "SELECT a.id, a.name FROM ".$this->table." a ";
        if ($public_only) {
            $sql .= " WHERE a.status = 1 AND a.type = " . self::TYPE_STATIC;
        } else {
            $sql .= " JOIN photos_album_rights r ON a.id = r.album_id AND ";
            if ($user->isAdmin('photos')) {
                $sql .= '(r.group_id >= 0 OR r.group_id = -i:contact_id)';
            } else {
                $group_ids = $user->getGroups();
                $group_ids[] = 0;
                $group_ids[] = -$user->getId();
                $sql .= 'r.group_id IN ('.implode(",", $group_ids).')';
            }
            $sql .= " WHERE a.type = " . self::TYPE_STATIC;
            if ($owned_only) {
                $sql .= " AND a.contact_id = i:contact_id";
            }
        }
        $sql .= " ORDER BY parent_id, sort";

        $data = $this->query($sql, array(
            'contact_id' => $user->getId(),
        ))->fetchAll('id');

        foreach ($data as $album_id => $album) {
            if ($album['parent_id']) {
                $data[$album['parent_id']]['albums'][] = $album_id;
            }
        }

        $result = array();
        foreach ($data as $album_id => $album) {
            if (!$album['parent_id']) {
                $result[$album_id] = $album['name'];
                if (isset($album['albums'])) {
                    $this->getChildAlbums($album_id, $data, $result, '-');
                }
            }
        }
        return $result;
    }

    public function getFirstChild($album_id)
    {
        if (!$album_id) {
            return false;
        }
        return $this->query("SELECT * FROM {$this->table} WHERE parent_id = ".(int)$album_id." ORDER BY sort LIMIT 1")->fetchAssoc();
    }

    private function getChildAlbums($album_id, $data, &$result, $prefix = '')
    {
        foreach ($data[$album_id]['albums'] as $id) {
            $album = $data[$id];
            $result[$id] = ' '.$prefix.' '.$album['name'];
            if (isset($album['albums'])) {
                $this->getChildAlbums($id, $data, $result, $prefix.'-');
            }
        }
    }

    public function getDescendant($album_id)
    {
        $descendant = array();
        $parent_ids = array($album_id);
        while ($parent_ids) {
            $where = $this->getWhereByField('parent_id', $parent_ids);
            $sql = "SELECT * FROM {$this->table} WHERE $where";
            $result = $this->query($sql);
            $parent_ids = array();
            foreach ($result as $item) {
                $descendant[$item['id']] = $item;
                $parent_ids[] = $item['id'];
            }
        }
        return $descendant;
    }

    public function getChildren($parent_id)
    {
        $user = wa()->getUser();
        if ($user->isAdmin('photos')) {
            $rights_sql = '(r.group_id >= 0 OR r.group_id = -'.$user->getId().')';
        } else {
            $group_ids = $user->getGroups();
            $group_ids[] = 0;
            $group_ids[] = -$user->getId();
            $rights_sql = 'r.group_id IN ('.implode(",", $group_ids).')';
        }

        $sql = "SELECT a.*, ac.count
                FROM ".$this->table." a
                JOIN photos_album_rights r
                    ON a.id = r.album_id
                        AND {$rights_sql}
                LEFT JOIN photos_album_count ac
                    ON a.id = ac.album_id
                        AND ac.contact_id = i:contact_id
                WHERE parent_id=i:parent_id
                ORDER BY sort";
        return $this->query($sql, array(
            'parent_id' => $parent_id,
            'contact_id' => $user->getId(),
        ))->fetchAll($this->id);
    }

    public function getAlbums($public_only = false, $type = null, $owned_only = false, $count = true)
    {
        $user = wa()->getUser();
        if($count) {
            $sql = "SELECT a.*, ac.count FROM ".$this->table." a LEFT JOIN
            photos_album_count ac ON a.id = ac.album_id AND ac.contact_id = i:contact_id";
        } else {
            $sql = "SELECT a.* FROM ".$this->table." a";
        }
        if ($public_only) {
            $sql .= " WHERE a.status = 1";
        } else {
            $sql .= " JOIN photos_album_rights r ON a.id = r.album_id AND ";
            if ($user->isAdmin('photos')) {
                $sql .= '(r.group_id >= 0 OR r.group_id = -i:contact_id)';
            } else {
                $group_ids = $user->getGroups();
                $group_ids[] = 0;
                $group_ids[] = -$user->getId();
                $sql .= 'r.group_id IN ('.implode(",", $group_ids).')';
            }
            $sql .= " WHERE 1";
            if ($type !== null) {
                $sql .= " AND a.type = " . $this->escape($type, 'int');
            }
            if ($owned_only) {
                $sql .= " AND a.contact_id = i:contact_id";
            }
        }
        $sql .= " ORDER BY parent_id, sort";
        $albums = $this->query($sql, array(
            'contact_id' => $user->getId(),
        ))->fetchAll($this->id);
        if ($count && $user->getId()) {

            $album_photos_model = new photosAlbumPhotosModel();
            $counter = $album_photos_model->getCountByAlbum();
            foreach($albums as $id => &$album) {
                $album['count_new'] = 0;
                if(isset($counter[$id])) {
                    $album['count_new'] = max(0, $counter[$id]-$album['count']);
                }
            }
            unset($album);
        }
        return $albums;
    }

    public function getAlbumsCounters($public_only = false, $type = null, $owned_only = false, $upload_datetime = null)
    {
        $user = wa()->getUser();
        $album_ids = array_keys($this->getAlbums($public_only, $type, $owned_only, false));
        if (!$album_ids) {
            return null;
        }
        $sql = "SELECT ap.album_id, COUNT(p.id) cnt FROM `photos_photo` p JOIN `photos_album_photos` ap ON p.id = ap.photo_id";
        if ($public_only) {
            $sql .= " WHERE a.status = 1";
        } else {
            $sql .= " JOIN photos_photo_rights pr ON p.id = pr.photo_id AND ";
            if ($user->isAdmin('photos')) {
                $sql .= '(pr.group_id >= 0 OR pr.group_id = -i:contact_id)';
            } else {
                $group_ids = $user->getGroups();
                $group_ids[] = 0;
                $group_ids[] = -$user->getId();
                $sql .= 'pr.group_id IN ('.implode(",", $group_ids).')';
            }
            $sql .= ' WHERE 1';
        }
        $sql .= ' AND ap.album_id IN ('.implode(',', $album_ids). ') AND parent_id = 0';
        if ($upload_datetime) {
            $sql .= " AND p.upload_datetime >= s:upload_datetime AND p.contact_id != i:contact_id";
        }
        $sql .= ' GROUP BY ap.album_id';
        return $this->query($sql, array(
            'contact_id' => $user->getId(),
            'upload_datetime' => $upload_datetime
        ))->fetchAll('album_id');
    }

    public function updateName($id, $name)
    {
        $this->updateById($id, array('name' => $name));
    }

    public function updateCount($id, $count)
    {
        $model = new photosAlbumCountModel();
        $model->replace(array(
            'contact_id' => waSystem::getInstance()->getUser()->getId(),
            'album_id' => $id,
            'count' => $count,
            //'datetime' => date("Y-m-d H:i:s")
        ));
    }

    /**
     * Move album to place just before $before_id in level with parent_id=$parent_id
     * @param int $album_id
     * @param int $before_id
     * @param int $parent_id
     * @return array album
     */
    public function move($album_id, $before_id, $parent_id)
    {
        // get necessary fields
        $sql = "SELECT id, url, parent_id FROM {$this->table} WHERE id = i:id";
        $album = $this->getById($album_id);
        $old_parent_id = $album['parent_id'];
        $url = $album['url'];

        // define correct sort value for moving (and shifting lower items if needed)
        if ($before_id) {
            $sql = "SELECT sort FROM {$this->table} WHERE parent_id = i:parent_id AND id = i:before_id";
            $sort = $this->query($sql, array(
                'parent_id' => $parent_id,
                'before_id' => $before_id
            ))->fetchField('sort');

            $sql = "UPDATE ".$this->table." SET sort = sort + 1
                    WHERE parent_id = i:parent_id AND sort >= i:sort";
            $this->exec($sql, array('parent_id' => $parent_id, 'sort' => $sort));
        } else {
            $sql = "SELECT sort FROM {$this->table} WHERE parent_id = i:parent_id ORDER BY sort DESC LIMIT 0, 1";
            $sort = $this->query($sql, array(
                'parent_id' => $parent_id
            ))->fetchField('sort') + 1;
        }

        // change url taking into account uniqueness of urls in one level of albums
        $url = $this->suggestUniqueUrl($url, $album_id, $parent_id);
        // make move
        $sql = "UPDATE {$this->table} SET sort = i:sort, parent_id = i:parent_id, url = s:url WHERE id = i:id";
        $update_data = array(
            'parent_id' => $parent_id,
            'sort' => $sort,
            'url' => $url,
            'id' => $album_id
        );
        $this->exec($sql, $update_data);
        $album = array_merge($album, $update_data);

        // correct full_url of this item and all of new descendants if parent has changed
        if ($old_parent_id != $parent_id) {
            $sql = "SELECT id, full_url, status FROM {$this->table} WHERE id = i:id";
            $data = $this->query($sql, array(
                'id' => $parent_id
            ))->fetch();

            $parent_full_url = $data ? $data['full_url'] : '';
            $parent_status = $data ? $data['status'] : 1;

            $sql = "SELECT id, url, status FROM {$this->table} WHERE id = i:id";
            $data = $this->query($sql, array(
                'id' => $album_id
            ))->fetch();

            $update = array();
            if ($parent_status == 1 && $data['status'] == 1) {
                $update['full_url'] = ltrim($parent_full_url . '/' . $url, '/');
            } else if ($data['status'] == 1) {
                $update['full_url'] = null;
                $update['status'] = 0;
                $update['hash'] = md5(uniqid(time(), true));
            }
            if ($update) {
                $this->updateById($album_id, $update);
                $album = array_merge($album, $update);
                if ($update['full_url']) {
                    $this->correctFullUrlOfDescendants($album_id, $update['full_url']);
                } else {
                    $this->privateDescendants($album_id);
                }
            }
        }

        return $album;
    }

    public function getByType($type)
    {
        $sql = "SELECT *
                FROM ".$this->table."
                WHERE type=:type
                ORDER BY name";
        return $this->query($sql, array('type' => $type))->fetchAll();
    }

    private function updateUrl($id, $url)
    {
        $sql = "SELECT id, parent_id, url, full_url FROM {$this->table} WHERE id = i:id";
        $data = $this->query($sql, array(
            'id' => $id
        ))->fetch();

        if ($data['parent_id']) {
            $parent = $this->getById($data['parent_id']);
            $full_url = trim($parent['full_url'], '/').'/'.$url;
        } else {
            $full_url = $url;
        }

        $this->updateById($id, array(
            'url' => $url,
            'full_url' => $full_url
        ));

        // update full_url all of descendant
        $this->correctFullUrlOfDescendants($data['id'], $full_url);
    }

    private function correctFullUrlOfDescendants($id, $full_url)
    {
        $parent_ids = array($id);
        $parent_full_url = array(
            $id => $full_url
        );
        while ($parent_ids) {
            $where = $this->getWhereByField('parent_id', $parent_ids);
            $sql = "SELECT id, parent_id, url, full_url FROM {$this->table} WHERE $where AND status = 1";
            $result = $this->query($sql);

            $parent_ids = array();
            foreach ($result as $item) {
                $full_url = $parent_full_url[$item['parent_id']] . '/' . $item['url'];
                $full_url = ltrim($full_url, '/');
                $parent_full_url[$item['id']] = $full_url;
                $parent_ids[] = $item['id'];
                $this->updateById($item['id'], array(
                    'full_url' => $full_url
                ));
            }
        }
    }

    private function privateDescendants($parent_id)
    {
        $update = array();
        $parent_ids = array((int)$parent_id);
        while ($parent_ids) {
            $sql = "SELECT id, status FROM {$this->table} WHERE parent_id IN(".implode(',', $parent_ids).")";
            $parent_ids = array();
            foreach ($this->query($sql) as $item)
            {
                if ($item['status'] == 1) {
                    $update[$item['id']] = array('status' => 0, 'hash' => md5(uniqid(time(), true)), 'full_url' => null);
                }
                $parent_ids[] = $item['id'];
            }
        }
        foreach ($update as $id => $data) {
            $this->updateById($id, $data);
        }
    }

    private function updateDescendants($parent_id, $data = array(), $include_parent = false)
    {
        if (empty($data)) {
            return false;
        }

        $descandants = array();
        if ($include_parent) {
            $descandants[] = $parent_id;
        }

        $parent_ids = array((int)$parent_id);
        $counter = 1;
        while ($parent_ids) {
            $parent_ids = array_keys($this->getByField('parent_id', $parent_ids, 'id'));
            $descandants = array_merge($descandants, $parent_ids);
        }
        return $this->updateById($descandants, $data);
    }

    /**
     * Update taking in account recursive nature of 'full_url' fields
     *
     * @param int $id
     * @param array $data
     */
    public function update($id, $data)
    {
        $item = $this->getById($id);
        if (!$item) {
            return false;
        }
        if (isset($data['url'])) {
            $url = $data['url'];
            unset($data['url']);
        }

        if (!isset($data['status'])) {
            $data['status'] = $item['status'];
        }

        if ($data['status'] <= 0) {
            $data['full_url'] = null;
            if (!isset($data['hash'])) {
                $data['hash'] = md5(uniqid(time(), true));
            }
        } else {
            unset($data['full_url']);
        }

        $this->updateById($id, $data);
        if ($data['status'] <= 0) {
            $this->privateDescendants($id);
        } elseif (isset($url)) {
            $this->updateUrl($id, $url);
        } else {
            $item = $this->getById($id);
            if (!$item['url']) {
                $url = suggestUniqueUrl(photosPhoto::suggestUrl($item['name']));
                $this->updateUrl($id, $url);
            } else if (!$item['full_url']) {
                $this->updateUrl($id, $item['url']);
            }
        }

        return true;

    }

    /**
     * Get breadcrumbs for album (list of parents)

     * @param int $id
     * @param boolean $escape
     * @param boolean $use_itself
     * @return array of items array('name' => '..', 'full_url' => '..')
     */
    public function getBreadcrumbs($album_id, $escape = false, $use_itself = false)
    {
        $breadcrumbs = array();

        while ($album_id) {
            $sql = "SELECT id, full_url, parent_id, name, status FROM {$this->table} WHERE id = i:id AND status = 1";
            $album = $this->query($sql, array(
                'id' => $album_id
            ))->fetch();
            if ($album) {
                $url = photosFrontendAlbum::getLink($album);
                $breadcrumbs[] = array(
                    'album_id' => $album['id'],
                    'name' => $escape ? photosPhoto::escape($album['name']) : $album['name'],
                    'full_url' => $url,
                    'url' => $url,
                    'status' => $album['status']
                );
                $album_id = $album['parent_id'];
            } else {
                $album_id = null;
            }
        }
        $breadcrumbs = array_reverse($breadcrumbs);
        if (!$use_itself) {
            array_pop($breadcrumbs);
        }
        return $breadcrumbs;
    }

    /**
     * Delete album with all relating info (+ taking into account children. Children 'jump' up one level)
     *
     * @param integer $album_id
     */
    public function delete($album_id)
    {
        $album_id = (int)$album_id;
        $sql = "SELECT sort, parent_id FROM {$this->table} WHERE id = i:album_id";
        $album = $this->query($sql, array(
            'album_id' => $album_id
        ))->fetch();
        $sort = $album['sort'];
        $parent_id = $album['parent_id'];

        $sql = "SELECT id, url FROM {$this->table} WHERE parent_id = i:parent_id";
        $children = $this->query($sql, array('parent_id' => $album_id))->fetchAll('id');
        $children_cnt = count($children);

        $related_models = array(
            new photosAlbumRightsModel(),
            new photosAlbumPhotosModel(),
            new photosAlbumParamsModel(),
            new photosAlbumCountModel()
        );

        foreach ($related_models as $model) {
            $model->deleteByField('album_id', $album_id);
        }
        $sql = "DELETE FROM {$this->table} WHERE id = i:album_id";
        $this->exec($sql, array('album_id' => $album_id));

        // workup children
        if ($children_cnt > 0) {
            $shift = $children_cnt - 1;
            if ($shift > 0) {
                $sql = "UPDATE ".$this->table." SET sort = sort + {$shift} WHERE parent_id = i:parent_id AND sort >= i:sort";
                $this->exec($sql, array('parent_id' => $parent_id, 'sort' => $sort));
            }
            // get parent info
            $sql = "SELECT id, full_url FROM {$this->table} WHERE id = i:id";
            $parent = $this->query($sql, array(
                'id' => $parent_id
            ))->fetch();
            $parent_full_url = $parent ? $parent['full_url'] : '';

            foreach ($children as $child) {
                // change url taking into account uniqueness of urls in one level of albums
                $url = $this->suggestUniqueUrl($child['url'], $child['id'], $parent_id);
                $full_url = ltrim($parent_full_url . '/' . $url, '/');
                // make move
                $sql = "UPDATE {$this->table} SET sort = i:sort, parent_id = i:parent_id, url = s:url, full_url = s:full_url WHERE id = i:id";
                $update_data = array(
                    'parent_id' => $parent_id,
                    'sort' => $sort,
                    'url' => $url,
                    'full_url' => $full_url,
                    'id' => $child['id']
                );
                $sort += 1;
                if ($this->exec($sql, $update_data)) {
                    $this->correctFullUrlOfDescendants($child['id'], $full_url);
                }
            }
        }

        return true;
    }

    public function deleteByField($field, $value = null) {
        $album_ids = array_keys($this->getByField($field, $value, $this->id));
        foreach ($album_ids as $album_id) {
            $this->delete($album_id);
        }
    }

    /**
     * Check if same url exists already for any albums in current level (parent_id), excepting this album (album_id)
     *
     * @param string $url
     * @param int $album_id optional. If set check urls excepting url of this album
     * @param int $parent_id Check albums of one level
     *
     * @return boolean
     */
    public function urlExists($url, $album_id = null, $parent_id = 0)
    {
        $where = "url = s:url AND parent_id = i:parent_id";
        if ($album_id) {
            $where .= " AND id != i:id";
        }
        return !!$this->select('id')->where($where, array(
            'url' => $url,
            'parent_id' => $parent_id,
            'id' => $album_id
        ))->fetch();
    }

    /**
     * Suggest unique url by original url.
     * If not exists yet just return without changes, otherwise fit a number suffix and adding it to url.
     * @see urlExists
     *
     * @param string $original_url
     * @param int $album_id Delegate to urlExists method
     * @param int $parent_id Delegate to urlExists method
     *
     * @return string
     */
    public function suggestUniqueUrl($original_url, $album_id = null, $parent_id = 0)
    {
        $counter = 1;
        $url = $original_url;
        while ($this->urlExists($url, $album_id, $parent_id)) {
            $url = "{$original_url}_{$counter}";
            $counter++;
        }
        return $url;
    }

    public function keyPhotos(&$albums)
    {
        $key_photo_ids = array();
        foreach($albums as $aid => $a) {
            if ($a['key_photo_id']) {
                $key_photo_ids[] = $a['key_photo_id'];
            }
        }

        $collection = new photosCollection($key_photo_ids);
        $photos = $collection->getPhotos('*,thumb_96x96,thumb_192x192');
        foreach($albums as &$a) {
            if ($a['key_photo_id'] && !empty($photos[$a['key_photo_id']])) {
                $a['key_photo'] = $photos[$a['key_photo_id']];
                $a['key_photo']['thumb'] = $a['key_photo']['thumb_192x192'];
            } else {
                if ($a['key_photo_id']) {
                    $this->updateById($a['id'], array(
                        'key_photo_id' => null,
                    ));
                }
                $a['key_photo'] = null;
            }
        }
        unset($a);

        return $albums;
    }
}
