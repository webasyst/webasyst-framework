<?php

class photosAlbumRightsModel extends waModel
{
    protected $table = 'photos_album_rights';

    public function setRights($album_id, $group_ids)
    {
        $rights = $this->getByField('album_id', $album_id, 'group_id');

        $new_group_ids = array();
        foreach ((array)$group_ids as $group_id) {
            if (!isset($rights[$group_id])) {
                $new_group_ids[] = $group_id;
            } else {
                unset($rights[$group_id]);
            }
        }
        if (!empty($rights)) {
            $this->deleteByField(array(
                'group_id' => array_keys($rights),
                'album_id' => $album_id
            ));
        }
        if (!empty($new_group_ids)) {
            return $this->multiInsert(array(
                'album_id' => $album_id,
                'group_id' => $new_group_ids
            ));
        }
        return true;
    }

    /**
     * @param array|int $album album or id of album
     * @param bool $check_edit
     */
    public function checkRights($album, $check_edit = false)
    {
        if (!is_array($album)) {
            $album_model = new photosAlbumModel();
            $album = $album_model->getById((int)$album);
        }
        if (!$album) {
            return false;
        }
        $album_id = $album['id'];
        $user = wa()->getUser();
        if ($check_edit && $album['contact_id'] != $user->getId() && !$user->getRights('photos', 'edit')) {
            return false;
        }
        if ($user->isAdmin()) {
            $where = "(group_id >= 0 OR group_id = -".(int)$user->getId().")";
        } else {
            $groups = wa()->getUser()->getGroupIds();
            $where = "group_id IN ('".implode("','", $groups)."')";
        }
        $sql = "SELECT count(*) FROM ".$this->table."
                WHERE album_id = ".(int)$album_id." AND ".$where."
                LIMIT 1";
        return (bool)$this->query($sql)->fetchField();
    }

    public function filterAllowedAlbumIds($album_ids, $check_edit = false)
    {
        $join = '';
        $where = $this->getWhereByField('album_id', (array) $album_ids);

        $user = wa()->getUser();
        if ($user->isAdmin()) {
            $where .= " AND (group_id >= 0 OR group_id = -".(int)$user->getId().")";
        } else {
            $groups = $user->getGroupIds();
            $where .= " AND group_id IN ('".implode("','", $groups)."')";
        }
        if ($check_edit && !$user->getRights('photos', 'edit')) {
            $join = " INNER JOIN photos_album a ON a.id = r.album_id AND a.contact_id = ".$user->getId();
        }

        $sql = "SELECT r.album_id FROM ".$this->table." r $join WHERE $where";
        return array_keys($this->query($sql)->fetchAll('album_id'));
    }
}