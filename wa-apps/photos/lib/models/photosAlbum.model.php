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

        return $this->query($sql, array(
            'contact_id' => $user->getId(),
        ))->fetchAll('id');
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
            'count' => $count
        ));
    }

    public function moveSort($id, $before_id, $parent_id)
    {
        // define current (old) parent
        $sql = "SELECT id, parent_id FROM {$this->table} WHERE id = i:id";
        $old_parent_id = $this->query($sql, array(
            'id' => $id
        ))->fetchField('parent_id');

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

        // make move
        $sql = "UPDATE {$this->table} SET sort = i:sort, parent_id = i:parent_id WHERE id = i:id";
        $this->exec($sql, array('parent_id' => $parent_id, 'sort' => $sort, 'id' => $id));

        // correct full_url of this item and all of new descendants if parent has changed
        if ($old_parent_id != $parent_id) {
            $sql = "SELECT id, full_url FROM {$this->table} WHERE id = i:id";
            $data = $this->query($sql, array(
            	'id' => $parent_id
            ))->fetch();

            $parent_full_url = $data ? $data['full_url'] : '';
            $sql = "SELECT id, url FROM {$this->table} WHERE id = i:id";
            $data = $this->query($sql, array(
                'id' => $id
            ))->fetch();

            $full_url = $parent_full_url . '/' . $data['url'];
            $full_url = ltrim($full_url, '/');

            $this->updateById($id, array(
                'full_url' => $full_url
            ));
            $this->correctFullUrlOfDescendants($id, $full_url);
        }
    }

    public function getByType($type)
    {
        $sql = "SELECT *
                FROM ".$this->table."
                WHERE type=:type
                ORDER BY name";
        return $this->query($sql, array('type' => $type))->fetchAll();
    }

    public function updateUrl($id, $url)
    {
        $sql = "SELECT id, parent_id, url, full_url FROM {$this->table} WHERE id = i:id";
        $data = $this->query($sql, array(
            'id' => $id
        ))->fetch();

        // update url, full_url
        $full_url = trim($data['full_url'], '/');
        $pos = strrpos($full_url, $data['url']);

        if ($pos === false) {
            $full_url = $url;
        } else {
            $full_url = substr($full_url, 0, $pos) . $url;
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
            $sql = "SELECT id, parent_id, url, full_url FROM {$this->table} WHERE $where";
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

    /**
     * Update taking in account recursive nature of 'full_url' fields
     *
     * @param int $id
     * @param array $data
     */
    public function update($id, $data)
    {
        if (isset($data['url'])) {
            $url = $data['url'];
            unset($data['url']);
        }
        $this->updateById($id, $data);
        if (isset($url)) {
            $this->updateUrl($id, $url);
        }
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
            $sql = "SELECT id, full_url, parent_id, name FROM {$this->table} WHERE id = i:id AND status = 1";
            $album = $this->query($sql, array(
                'id' => $album_id
            ))->fetch();
            if ($album) {
                $breadcrumbs[] = array(
                    'name' => $escape ? photosPhoto::escape($album['name']) : $album['name'],
                    'full_url' => photosFrontendAlbum::getLink($album)
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
    * Get "childcrumbs" (sub-albums) for album (list of childrens)

    * @param int $id
    * @param bool $escape
    * @return array of items array('name' => '..', 'full_url' => '..')
    */
    public function getChildcrumbs($id, $escape = false)
    {

        $sql = "SELECT full_url, name FROM {$this->table} WHERE parent_id = i:id AND status = 1";
        $result = $this->query($sql, array(
            'id' => $id
        ));
        $childcrumbs = array();
        foreach ($result as $album) {
            $childcrumbs[] = array(
                'name' => $escape ? photosPhoto::escape($album['name']) : $album['name'],
                'full_url' => photosFrontendAlbum::getLink($album)
            );
        }
        return $childcrumbs;
    }

    /**
     * Delete album with all relating info
     *
     * @param integer|array $id one or several ids
     * @param bool $del_photos
     */
    public function delete($id, $del_photos = false)
    {
        $success = true;
        if ($del_photos) {
            $success = false;
            $album_photos = new photosAlbumPhotosModel();
            if ($where = $album_photos->getWhereByField('album_id', $id)) {
                $sql = "SELECT DISTINCT photo_id FROM ".$album_photos->getTableName()." WHERE $where";
                $photo_ids = array_keys($this->query($sql)->fetchAll('photo_id'));
                $photo_model = new photosPhotoModel();
                $success = $photo_model->deleteById($photo_ids);
            }
        }
        if (!$success) {
            return false;
        }
        $related_models = array('AlbumRights', 'AlbumPhotos', 'AlbumParams', 'AlbumCount');

        foreach ($related_models as $name) {
            $model_name = 'photos'.$name.'Model';
            $model = new $model_name;
            $model->deleteByField('album_id', $id);
        }

        return $this->deleteById($id);
    }
}