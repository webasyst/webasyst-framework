<?php

class photosTagModel extends waModel
{

    const CLOUD_MAX_SIZE = 150;
    const CLOUD_MIN_SIZE = 80;
    const CLOUD_MAX_OPACITY = 100;
    const CLOUD_MIN_OPACITY = 30;

    protected $table = 'photos_tag';

    public function decreaseCounters($id, $n = 1)
    {
        if ($where = $this->getWhereByField('id', $id)) {
            $counts_list = $this->query('SELECT id, count FROM '. $this->table . ' WHERE ' . $where)->fetchAll('id', true);
            $delete = array();
            $update = array();
            foreach ($counts_list as $id => $count) {
                $count -= $n;
                if ($count <= 0) {
                    $delete[] = $id;
                } else {
                    $update[] = $id;
                }
            }
            if (!empty($delete)) {
                $this->query('DELETE FROM ' . $this->table . ' WHERE ' . $this->getWhereByField('id', $delete));
            }
            if (!empty($update)) {
                $this->query('UPDATE ' . $this->table . ' SET count = count - '.(int)$n.' WHERE ' . $this->getWhereByField('id', $update));
            }
        }
    }

//    public function getIds($names)
//    {
//        $tags = (array) $names;
//        $existed_tags = $this->getByField('name', $tags, 'name');
//        $tag_ids = array();
//        foreach ($tags as $tag) {
//            if (!isset($existed_tags[$tag]) && $tag) {
//                $tag_id = $this->insert(array(
//                    'name' => $tag
//                ));
//            } else {
//                $tag_id = $existed_tags[$tag]['id'];
//            }
//            $tag_ids[$tag_id] = $tag_id;
//        }
//
//        return $tag_ids;
//    }
    
    public function getIds($tags)
    {
        $tags = (array) $tags;
        $result = array();
        foreach ($tags as $t) {
            $t = trim($t);
            if ($id = $this->getByName($t, true)) {
                $result[] = $id;
            } else {
                $result[] = $this->insert(array('name' => $t));
            }
        }
        return $result;
    }

    public function getByName($name, $return_id = false)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE name LIKE '".$this->escape($name, 'like')."'";
        $row = $this->query($sql)->fetch();
        return $return_id ? (isset($row['id']) ? $row['id'] : null) : $row;
    }

    public function getByPhoto($photo_id)
    {
        if (!$photo_id) {
            throw new Exception("Can't get tags: unkown photo id");
        }

        $photos_photo_tags_model = new photosPhotoTagsModel();
        $tag_ids = $photos_photo_tags_model->getTagIds($photo_id);

        $tags = $this->select('name')->where(
            $this->getWhereByField('id', $tag_ids)
        )->fetchAll('name', true);

        return array_keys($tags);
    }

    public function getCloud($key = null)
    {
        $tags = $this->where('count > 0')->fetchAll($key);
        if (!empty($tags)) {
            $first = current($tags);
            $max_count = $min_count = $first['count'];
            foreach ($tags as $tag) {
                if ($tag['count'] > $max_count) {
                    $max_count = $tag['count'];
                }
                if ($tag['count'] < $min_count) {
                    $min_count = $tag['count'];
                }
            }
            $diff = $max_count - $min_count;
            $diff = $diff <= 0 ? 1 : $diff;
            $step_size = (self::CLOUD_MAX_SIZE - self::CLOUD_MIN_SIZE) / $diff;
            $step_opacity = (self::CLOUD_MAX_OPACITY - self::CLOUD_MIN_OPACITY) / $diff;
            foreach ($tags as &$tag) {
                $tag['size'] = ceil(self::CLOUD_MIN_SIZE + ($tag['count'] - $min_count) * $step_size);
                $tag['opacity'] = number_format((self::CLOUD_MIN_OPACITY + ($tag['count'] - $min_count) * $step_opacity) / 100, 2, '.', '');
                $tag['uri_name'] = urlencode($tag['name']);
            }
            unset($tag);
        }
        return $tags;
    }
    
    public function popularTags($limit = 10)
    {
        return $this->select('*')->order('count DESC')->limit($limit)->fetchAll();
    }
}
