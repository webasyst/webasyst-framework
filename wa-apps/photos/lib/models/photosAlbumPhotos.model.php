<?php

class photosAlbumPhotosModel extends waModel
{
    protected $table = 'photos_album_photos';

    public function getByPhoto($photo_id)
    {
        $sql = "SELECT album_id
                FROM ".$this->table."
                WHERE photo_id = i:id";
        return $this->query($sql, array('id' => $photo_id))->fetchAll('album_id', true);
    }

    public function set($photo_id, $albums = array())
    {
        $photo_id = (int) $photo_id;
        if (!$photo_id) {
            throw new waException("Can't set albums: unkown photo id");
        }
        $photo_id = $photo_id = $this->_resolvePhotoIds($photo_id);
        $albums = (array) $albums;
        foreach ($photo_id as $id) {
            $this->_set($id, $albums);
        }
    }

    public function _set($photo_id, $albums = array())
    {
        $delete_albums = $this->getByField('photo_id', $photo_id, 'album_id');

        foreach ($albums as $album_id) {
            if (!isset($delete_albums[$album_id])) {
                $sort = (int)$this->query("SELECT sort + 1 AS sort FROM " . $this->table .
                    " WHERE album_id = i:album_id ORDER BY sort DESC LIMIT 1", array('album_id' => $album_id)
                )->fetchField('sort');
                $this->insert(array(
                    'album_id' => $album_id,
                    'photo_id' => $photo_id,
                    'sort' => $sort
                ));
            } else {
                unset($delete_albums[$album_id]);
            }
        }

        $delete_album_ids = array_keys($delete_albums);
        $this->deleteByField(array(
            'album_id' => $delete_album_ids,
            'photo_id' => $photo_id
        ));
    }

    public function add($photo_id, $albums = array(), $check_stacks = true)
    {
        if (!$photo_id) {
            throw new waException("Can't add albums: unkown any photo id");
        }
        if ($check_stacks) {
            $photo_id = $this->_resolvePhotoIds($photo_id);
        }
        $albums = (array) $albums;

        $sql = "SELECT * FROM {$this->table} ";
        if ($where = $this->getWhereByField('photo_id', $photo_id)) {
            $sql .= " WHERE $where";
        }
        $existed_albums = array();
        foreach ($this->query($sql) as $item) {
            $existed_albums[$item['photo_id']][$item['album_id']] = true;
        }

        $last_sort = array();
        foreach ($photo_id as $id) {
            $add = array();
            foreach ($albums as $album_id) {
                if (!isset($existed_albums[$id][$album_id])) {
                    if (!isset($last_sort[$album_id])) {
                        $last_sort[$album_id] = (int)$this->query("SELECT sort + 1 AS sort FROM " . $this->table .
                            " WHERE album_id = i:album_id ORDER BY sort DESC LIMIT 1", array('album_id' => $album_id)
                        )->fetchField('sort');
                    } else {
                        ++$last_sort[$album_id];
                    }
                    $add[] = array(
                        'photo_id' => $id,
                        'album_id' => $album_id,
                        'sort' => $last_sort[$album_id]
                    );
                }
            }
            if (!empty($add)) {
                $this->multiInsert($add);
            }
        }
    }

    /**
     * Photo ids resolution: get photo ids taking into account photos in stacks (with saving sorting + childrend photos after parent)
     * @param int|array $photo_id
     */
    private function _resolvePhotoIds($photo_id)
    {
        $photo_model = new photosPhotoModel();
        return array_keys($photo_model->getPhotos((array)$photo_id));
    }

    public function getAlbums($photo_id, $fields = null, $public_only = false)
    {
        if ($fields) {
            if (is_array($fields)) {
                foreach ($fields as &$f) {
                    $f = "a.$f";
                }
                unset($f);
                $fields = implode(',', $fields);
            }
        } else {
            $fields = 'a.*';
        }

        $sql = "SELECT $fields, ap.photo_id FROM {$this->table} ap INNER JOIN photos_album a ON a.id = ap.album_id";

        if ($public_only) {
            $sql .= " WHERE a.status = 1 AND a.type = " . photosAlbumModel::TYPE_STATIC;
        } else {
            $user = wa()->getUser();
            $sql .= " JOIN photos_album_rights r ON a.id = r.album_id AND ";
            if ($user->isAdmin('photos')) {
                $sql .= '(r.group_id >= 0 OR r.group_id = -'.$user->getId().')';
            } else {
                $group_ids = $user->getGroups();
                $group_ids[] = 0;
                $group_ids[] = -$user->getId();
                $sql .= 'r.group_id IN ('.implode(",", $group_ids).')';
            }
            $sql .= " WHERE 1";
        }
        if ($where = $this->getWhereByField('photo_id', $photo_id)) {
            $sql .= " AND $where";
        }
        $data = $this->query($sql);
        $result = array();
        foreach($data as $row) {
            $r = &$result[$row['photo_id']][$row['id']];
            $r = $row;
            unset($r['photo_id']);
        }
        unset($r);
        return $result;
    }

    /**
     * Move photo(s) inside album to place just before photo with id=$before_id
     * @param int|array $photo_id
     * @param int $album_id
     * @param int|null $before_id If null than photo move to the end of album
     */
    public function movePhoto($photo_id, $album_id, $before_id = null)
    {
        if ($before_id) {
            $sql = "SELECT sort FROM {$this->table} WHERE album_id = i:album_id AND photo_id = i:photo_id";
            $sort = $this->query($sql, array(
                        'album_id' => $album_id,
                        'photo_id' => $before_id
            ))->fetchField('sort');
            $photo_id = (array) $photo_id;
            $shift = count($photo_id);
            $sql = "UPDATE {$this->table} SET sort = sort + $shift
                        WHERE album_id = i:album_id AND sort >= i:sort";
            $this->exec($sql, array(
                'album_id' => $album_id,
                'sort' => $sort
            ));
        } else {
            $sql = "SELECT sort FROM {$this->table} WHERE album_id = i:album_id ORDER BY sort DESC LIMIT 0,1";
            $sort = $this->query($sql, array(
                'album_id' => $album_id
            ))->fetchField('sort') + 1;
            $photo_id = (array) $photo_id;
        }
        foreach ($photo_id as $id) {
            $this->updateByField(array(
                'album_id' => $album_id,
                'photo_id' => $id
            ), array(
                'sort' => $sort++
            ));
        }
    }

    public function lastUploadedCounters($datetime, $album_id = null)
    {
        $sql = "SELECT ap.album_id, COUNT(p.id) count FROM {$this->table} ap
                INNER JOIN photos_photo p ON p.id = ap.photo_id ";
        if ($datetime) {
            $sql .= " WHERE p.upload_datetime > '$datetime' " . ($album_id ? " AND ap.album_id = " . (int)$album_id : '');
            $sql .= " AND p.contact_id != ".wa()->getUser()->getId();
        }
        $sql .= " GROUP BY ap.album_id";

        return $this->query($sql)->fetchAll('album_id', true);
    }

    public function getCountByAlbum()
    {
        $photo = new photosPhotoModel();
        $sql = "SELECT COUNT(1) as cnt, a.album_id FROM {$this->table} a JOIN {$photo->getTableName()} p ON (p.{$photo->getTableId()} = a.photo_id) WHERE NOT p.parent_id GROUP BY a.album_id";
        return $this->query($sql)->fetchAll('album_id', true);
    }

    /**
     * Get photos from album(s) or photos that aren't inside any album
     *
     * @param int|array|null $album_id Get photos from this album(s). If $album is null that get photos that aren't inside any album
     * @param boolean $public_only
     */
    public function getPhotos($album_id = null, $public_only = true)
    {
        if ($album_id === null) {
            $join = "LEFT JOIN";
            $where = "ap.album_id IS NULL";
        } else {
            $join = "INNER JOIN";
            $where = $this->getWhereByField('album_id', $album_id);
        }
        if ($where) {
            if ($public_only) {
                $where .= " AND p.status = 1";
            }
            $sql = "SELECT p.*, ap.album_id FROM photos_photo p $join {$this->table} ap ON p.id = ap.photo_id WHERE $where ORDER BY ap.album_id";
            return $this->query($sql)->fetchAll();
        }
        return array();
    }
    
    /**
     * Delete photos from album
     *
     * @param int $album_id
     * @param array $photo_ids
     * @return boolean
     */
    public function deletePhotos($album_id, $photo_ids = array())
    {
        if (!$album_id) {
            return false;
        }
        if (!$this->deleteByField(array('album_id' => $album_id, 'photo_id' => $photo_ids))) {
            return false;
        }
        return true;
    }
}