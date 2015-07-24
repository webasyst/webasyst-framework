<?php


class photosCollection
{
    protected $hash;
    protected $check_rights = true;
    protected $frontend_base_url;
    protected $frontend_rights_user;
    protected $where;
    protected $order_by = 'p.upload_datetime DESC,p.id';
    protected $joins;

    protected $options = array(
    //'check_rights' => false
    );
    protected $prepared = false;

    protected $title = '';
    protected $count;
    protected $post_fields = array();
    protected $models = array();

    protected $album;

    protected $update_count = array();


    /**
     * Constructor for collections of photos
     *
     * @param string|array $hash
     * @param array $options
     */
    public function __construct($hash = '', $options = array())
    {
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
        $this->setHash($hash);
    }

    protected function setHash($hash)
    {
        if (is_array($hash)) {
            $hash = '/id/'.implode(',', $hash);
        }
        if (substr($hash, 0, 1) == '#') {
            $hash = substr($hash, 1);
        }
        $this->hash = trim($hash, '/');
        if ($this->hash == 'all') {
            $this->hash = '';
        }
        $this->frontend_base_url = $this->frontend_base_url ? $this->frontend_base_url : $this->hash;
        $this->hash = explode('/', $this->hash);
    }

    public function getSQL()
    {
        $this->prepare();
        $sql = "FROM photos_photo p";

        if ($this->joins) {
            foreach ($this->joins as $join) {
                $alias = isset($join['alias']) ? $join['alias'] : '';
                if (isset($join['on'])) {
                    $on = $join['on'];
                } else {
                    $on = "p.id = ".($alias ? $alias : $join['table']).".photo_id";
                }
                $sql .= (isset($join['type']) ? " ".$join['type'] : '')." JOIN ".$join['table']." ".$alias." ON ".$on;
            }
        }

        $where = $this->where;
        $where[] = 'p.parent_id = 0';

        if ($where) {
            $sql .= " WHERE ".implode(" AND ", $where);
        }
        return $sql;
    }


    protected function getFields($fields)
    {
        $photo_model = $this->getModel();
        if ($fields == '*') {
            return 'p.*';
        }

        $required_fields = array('id' => 'p', 'ext' => 'p', 'hash' => 'p', 'status' => 'p'); // field => table, to be added later in any case

        if (!is_array($fields)) {
            $fields = explode(",", $fields);
            $fields = array_map('trim', $fields);
        }

        // Add required fields to select and delete fields for getting data after query
        foreach ($fields as $i => $f) {
            if ($f == '*') {
                $fields[$i] = 'p.*';
                continue;
            }
            if (!$photo_model->fieldExists($f)) {
                if ($f == 'thumb' || $f === 'frontend_link' || $f === 'edit_rights' || substr($f, 0, 6) == 'thumb_') {
                    $this->post_fields['_internal'][] = $f;
                } elseif ($f == 'tags') {
                    $this->post_fields['tags'] = true;
                }
                unset($fields[$i]);
                continue;
            }

            if (isset($required_fields[$f])) {
                $fields[$i] = ($required_fields[$f] ? $required_fields[$f]."." : '').$f;
                unset($required_fields[$f]);
            }
        }

        foreach ($required_fields as $field => $table) {
            $fields[] = ($table ? $table."." : '').$field;
        }

        return implode(",", $fields);
    }


    /**
     * Returns expression for SQL
     *
     * @param string $op - operand ==, >=, etc
     * @param string $value - value
     * @return string
     */
    protected function getExpression($op, $value)
    {
        $model = $this->getModel();
        switch ($op) {
            case '>':
            case '>=':
            case '<':
            case '<=':
            case '!=':
                return " ".$op." '".$model->escape($value)."'";
            case "^=":
                return " LIKE '".$model->escape($value, 'like')."%'";
            case "$=":
                return " LIKE '%".$model->escape($value, 'like')."'";
            case "*=":
                return " LIKE '%".$model->escape($value, 'like')."%'";
            case "==":
            case "=";
            default:
                return " = '".$model->escape($value)."'";
        }
    }

    protected function prepare($add = false, $auto_title = true)
    {
        if (!$this->prepared || $add) {
            $type = $this->hash[0];
            if ($type) {
                $method = strtolower($type).'Prepare';
                if (method_exists($this, $method)) {
                    $this->$method(isset($this->hash[1]) ? $this->hash[1] : '', $auto_title);
                } else {
                    $params = array(
                        'collection' => $this,
                        'auto_title' => $auto_title,
                        'add' => $add,
                        'hash' => $this->hash,
                        'options' => $this->options
                    );
                    /**
                     * @event collection
                     * @param array[string]mixed $params
                     * @param array[string]photosCollection $params['collection']
                     * @param array[string]boolean $params['auto_title']
                     * @param array[string]boolean $params['add']
                     * @param array[string]array $params['options']
                     * @return bool null if ignored, true when something changed in the collection
                     */
                    $processed = wa()->event('collection', $params);
                    if (!$processed) {
                        throw new waException('Unknown collection hash type: '.htmlspecialchars($type));
                    }
                }
            } elseif ($auto_title) {
                $this->order_by = 'p.upload_datetime DESC,p.id';
                $this->addTitle(_w('All photos'));
            }

            $params = array(
                'collection' => $this,
                'auto_title' => $auto_title,
                'add' => $add,
                'hash' => $this->hash,
                'options' => $this->options,
            );
            /**
             * @event extra_prepare_collection
             * @param array[string]mixed $params
             * @param array[string]photosCollection $params['collection']
             * @param array[string]boolean $params['auto_title']
             * @param array[string]boolean $params['add']
             * @param array[string]array $params['options']
             */
            wa()->event('extra_prepare_collection', $params);

            if ($this->prepared) {
                return;
            }
            $this->prepared = true;
            // rights
            if ($this->check_rights) {
                if (wa()->getEnv() == 'frontend') {
                    if ($this->frontend_rights_user) {
                        $this->addRightsCondition(new waUser($this->frontend_rights_user));
                    } else {
                        $this->where[] = 'p.status = 1 AND p.url IS NOT NULL AND LENGTH(TRIM(p.url)) > 0';
                    }
                } else {
                    $this->addRightsCondition(wa()->getUser());
                }
            }
        }
    }


    protected function addRightsCondition(waUser $user)
    {
        if ($user->isAdmin('photos')) {
            $on = "(pr.group_id >= 0 OR pr.group_id = -".$user->getId().")";
        } else {
            $group_ids = $user->getGroupIds();
            $on = 'pr.group_id IN ('.implode(",", $group_ids).')';
        }
        $this->joins[] = array(
            'table' => 'photos_photo_rights',
            'alias' => 'pr',
            'on' => 'p.id = pr.photo_id AND '.$on
        );
    }

    protected function idPrepare($ids)
    {
        $ids = explode(',', $ids);
        $hash_ids = array();
        foreach ($ids as $k => $v) {
            $hash = false;
            if (strpos($v, ':') !== false) {
                list($v, $hash) = explode(':', $v, 2);
            }
            $v = (int)$v;
            if ($v) {
                if ($hash) {
                    $hash_ids[$v] = $hash;
                } else {
                    $ids[$k] = $v;
                }
            } else {
                unset($ids[$k]);
            }
        }
        if ($hash_ids) {
            // check hash
            $photo_model = $this->getModel();
            $data = $photo_model->select('id,hash')->where('id IN (?)', array_keys($hash_ids))->fetchAll('id', true);
            foreach ($hash_ids as $id => $hash) {
                if (!isset($data[$id]) || $data[$id] !== $hash) {
                    unset($hash_ids[$id]);
                    $ids[] = $id;
                }
            }
            // check rights to other photos
            if ($ids) {
                $user = wa()->getUser();
                if ($user->isAdmin('photos')) {
                    $on = "(pr.group_id >= 0 OR pr.group_id = -".$user->getId().")";
                } else {
                    $group_ids = $user->getGroupIds();
                    $on = 'pr.group_id IN ('.implode(",", $group_ids).')';
                }
                $sql = "SELECT p.id FROM photos_photo p
                        JOIN photos_photo_rights pr
                        ON p.id = pr.photo_id AND ".$on;
                $data = array();
                foreach ($photo_model->query($sql) as $row) {
                    $data[$row['id']] = 1;
                }
                foreach ($ids as $k => $v) {
                    if (!isset($data[$v])) {
                        unset($ids[$k]);
                    }
                }
            }
            foreach ($hash_ids as $id => $hash) {
                $ids[] = $id;
            }
            if ($ids) {
                $this->check_rights = false;
            }
        }
        if ($ids) {
            $this->where[] = "p.id IN (".implode(",", $ids).")";
        }
    }

    /**
     * @param int $id - ID of the album
     * @param bool $auto_title
     */
    protected function albumPrepare($id, $auto_title = true)
    {
        if (strpos($id, ':') !== false) {
            list($id, $hash) = explode(':', $id);
            $this->frontend_rights_user = (int)substr($hash, 16, -16);
            $hash = substr($hash, 0, 16).substr($hash, -16);
        }
        $album_model = new photosAlbumModel();
        $album = $album_model->getById($id);
        $this->setAlbum($album);

        // album not found or incorrect hash
        if (!$album || (isset($hash) && $hash !== $album['hash'])) {
            $this->where[] = '0';
            return;
        }

        $this->frontend_base_url = $album['full_url'];

        if ($auto_title) {
            $this->addTitle($album['name']);
        }

        $count_model = new photosAlbumCountModel();
        $this->update_count = array(
            'model' => $album_model,
            'id' => $id,
            'count' => (int)$count_model->getCount($id)
        );

        if ($album['type']) {
            $this->setHash('/search/'.$album['conditions']);
            $this->prepare(false, false);
            $params_model = new photosAlbumParamsModel();
            $params = $params_model->get($album['id']);
            if (isset($params['order']) && $params['order'] == 'rate') {
                $this->order_by = 'p.rate DESC, p.id DESC';
            }
            while ($album['parent_id'] && $album['type']) {
                $album = $album_model->getByid($album['parent_id']);
                if ($album['type']) {
                    $this->setHash('/search/'.$album['conditions']);
                    $this->prepare(true, false);
                } else {
                    $this->joins['photos_album_photos'] = array(
                        'table' => 'photos_album_photos',
                        'alias' => 'ap',
                    );
                    $this->where[] = "ap.album_id = ".(int)$album['id'];
                }
            }
        } else {
            $this->joins['photos_album_photos'] = array(
                'table' => 'photos_album_photos',
                'alias' => 'ap',
            );
            $this->where[] = "ap.album_id = ".(int)$id;
            $this->order_by = 'ap.sort ASC';
        }
    }

    protected function tagPrepare($id, $auto_title = true)
    {
        $tag_model = new photosTagModel();
        $tag = false;
        if (is_numeric($id)) {
            $tag = $tag_model->getById($id);
        }
        if (!$tag) {
            $tag = $tag_model->getByName($id);
            if ($tag) {
                $id = (int) $tag['id'];
            }
        }
        $this->joins['tags'] = array(
            'table' => 'photos_photo_tags',
            'alias' => 'pt',
        );
        if (!$tag && strstr($id, ',') !== false) {
            $this->tagPrepareIntersection($id);
        } else {
            $this->where[] = "pt.tag_id = ".(int)$id;
            $this->addTitle( sprintf( _w('Tagged “%s”'), $tag['name'] ) );
        }
    }

    protected function appPrepare($app_id, $auto_title = true)
    {
        $this->setCheckRights(false);
        $model = $this->getModel();
        $this->where[] = "p.app_id = '".$model->escape($app_id)."'";

        if ($auto_title) {
            $name = $app_id;
            $apps = wa()->getApps();
            if (!empty($apps[$app_id])) {
                $name = $apps[$app_id]['name'];
            }
            $this->addTitle($name);
        }
    }

    protected function tagPrepareIntersection($tag_names)
    {
        $tag_model = new photosTagModel();

        $in = array();
        $title = array();
        foreach (explode(',', $tag_names) as $tag_name) {
            $tag = $tag_model->getByName($tag_name);
            if ($tag) {
                $in[] = (int) $tag['id'];
                $title[] = $tag['name'];
            }
        }
        if (!$in) {
            $this->where[] = "0";
        } else {
            $sql = "SELECT photo_id, COUNT(tag_id) cnt FROM `photos_photo_tags`
                WHERE tag_id IN (".  implode(',', $in).")
                GROUP BY photo_id
                HAVING cnt = ".count($in);
            $photo_id = array_keys($tag_model->query($sql)->fetchAll('photo_id'));
            if ($photo_id) {
                $this->where[] = "p.id IN (".implode(',', $photo_id).")";
                $this->addTitle( sprintf( _w('Tagged “%s”'), implode(',', $title) ) );
            } else {
                $this->where[] = "0";
            }
        }
    }

    protected function authorPrepare($contact_id)
    {
        $this->where[] = 'p.contact_id='.(int)$contact_id;
        $this->order_by = 'p.upload_datetime DESC,p.id';
        $contact = new waContact($contact_id);
        $this->addTitle(_w('Uploaded by') . ' ' . $contact->getName());
    }

    protected function favoritesPrepare()
    {
        $this->where[] = "p.rate > 0";
        $this->order_by = 'p.rate DESC,p.id';
        $this->addTitle(_w('Editors’ choice'));
    }

    public static function escapePhotoFields(&$photos)
    {
        foreach ($photos as &$photo) {
            $photo = photosPhoto::escapeFields($photo);
        }
        unset($photo);
    }

    public static function parseConditions($query)
    {
        $escapedBS = 'ESCAPED_BACKSLASH';
        while(FALSE !== strpos($query, $escapedBS)) {
            $escapedBS .= rand(0, 9);
        }
        $escapedAmp = 'ESCAPED_AMPERSAND';
        while(FALSE !== strpos($query, $escapedAmp)) {
            $escapedAmp .= rand(0, 9);
        }
        $query = str_replace('\\&', $escapedAmp, str_replace('\\\\', $escapedBS, $query));
        $query = explode('&', $query);
        $result = array();
        foreach ($query as $part) {
            if (! ( $part = trim($part))) {
                continue;
            }
            $part = str_replace(array($escapedBS, $escapedAmp), array('\\\\', '\\&'), $part);
            if ($temp = preg_split("/(\\\$=|\^=|\*=|==|!=|>=|<=|=|>|<)/uis", $part, 2, PREG_SPLIT_DELIM_CAPTURE)) {
                $name = array_shift($temp);
                if ($name == 'tag') {
                    $temp[1] = explode('||', $temp[1]);
                }
                $result[$name] = $temp;
            }
        }
        return $result;
    }

    /**
     * Translate frontend album url to hash looks like 'album/<album_id>[:<hash>]'.
     * Take into account private hash
     *
     * @param string $album_url full url or string looks like album:<hash>
     * @param array &$album If $album_url is path to real album than album will be retrieved to this parameter
     * @return string $hash Hash of collection
     */
    public static function frontendAlbumUrlToHash($album_url, &$album = null)
    {
        $hash = '';
        if ($album_url) {
            $album_model = new photosAlbumModel();
            if (wa()->getEnv() == 'frontend') {
                $album = $album_model->getByField(array(
                    'full_url' => $album_url,
                    'status' => 1
                ));
            } else {
                $album = $album_model->getByField('full_url', $full_url);
            }
            if (!$album) {
                $parts = explode(':', $album_url);
                if (count($parts) == 2 && $parts[0] == 'album') {
                    $full_private_hash = $parts[1];
                    $private_hash = substr_replace($full_private_hash, '', 16, -16);
                    $album = $album_model->getByField('hash', $private_hash);
                }
            }
            if ($album) {    // album collection
                $album_id = $album['id'];
                $hash = 'album/'.$album_id .(isset($full_private_hash) ? ':'.$full_private_hash : '');
            } else {  // other kind of collection
                $hash = $album_url;
            }
        }
        return $hash;
    }

    public static function frontendAlbumHashToUrl($hash)
    {
        if (strstr($hash, 'album') !== false) {
            if (substr($hash, 0, 1) == '#') {
                $hash = substr($hash, 1);
            }
            $hash = trim($hash, '/');
            $hash = explode('/', $hash);

            if (count($hash) == 2) {
                $album_id = $hash[1];
                if (strpos($album_id, ':') !== false) {
                    list($album_id, $private_hash) = explode(':', $album_id);
                    return 'album:'.$private_hash;
                }
                $album_model = new photosAlbumModel();
                $album = $album_model->getById($album_id);
                if ($album['hash'] && $album['status'] <= 0) {
                    return 'album:'.substr($album['hash'], 0, 16).wa()->getUser()->getId().substr($album['hash'], 16);
                } else {
                    return $album['full_url'];
                }
            }
        }
        return null;
    }

    /**
     * @param string $hash
     * @param boolean $check_album
     * @return string
     */
    public static function getFrontendLink($hash = '', $check_album = true)
    {
        if ($check_album) {
            $url = self::frontendAlbumHashToUrl($hash);
            if (strlen($url)) {
                $link = photosFrontendAlbum::getLink($url);
                return $link;
            }
        }
        // another type of collection
        if (substr($hash, 0, 1) == '#') {
            $hash = substr($hash, 1);
        }
        $hash = trim($hash, '/');
        $hash = explode('/', $hash);

        $params = array();
        if (count($hash) >= 2) {
            $params[$hash[0]] = $hash[1];
        } else if (count($hash) == 1) {
            $params[$hash[0]] = $hash[0];
        }
        $link = wa()->getRouteUrl('photos/frontend', $params, true, wa()->getRouting()->getDomain(null, true, false));
        return $link ? rtrim($link, '/').'/' : null;
    }

    private function setAlbum($album)
    {
        $this->album = $album;
    }

    public function getAlbum()
    {
        return $this->album;
    }

    protected function searchPrepare($query, $auto_title = true)
    {
        $query = urldecode($query);

        // `&` can be escaped in search request. Need to split by not escaped ones only.
        $escapedBS = 'ESCAPED_BACKSLASH';
        while(FALSE !== strpos($query, $escapedBS)) {
            $escapedBS .= rand(0, 9);
        }
        $escapedAmp = 'ESCAPED_AMPERSAND';
        while(FALSE !== strpos($query, $escapedAmp)) {
            $escapedAmp .= rand(0, 9);
        }
        $query = str_replace('\\&', $escapedAmp, str_replace('\\\\', $escapedBS, $query));
        $query = explode('&', $query);

        $model = $this->getModel();
        $title = array();
        foreach ($query as $part) {
            if (! ( $part = trim($part))) {
                continue;
            }
            $part = str_replace(array($escapedBS, $escapedAmp), array('\\\\', '\\&'), $part);
            $parts = preg_split("/(\\\$=|\^=|\*=|==|!=|>=|<=|=|>|<)/uis", $part, 2, PREG_SPLIT_DELIM_CAPTURE);
            if ($parts) {
                if ($parts[0] == 'album') {
                    if (!isset($this->joins['photos_album_photos'])) {
                        $this->joins['photos_album_photos'] = array(
                            'table' => 'photos_album_photos',
                            'alias' => 'ap'
                            );
                    }
                    $title[] = "Album ".$parts[1].$parts[2];
                    $this->where[] = 'ap.album_id'.$this->getExpression($parts[1], $parts[2]);
                } elseif ($parts[0] == 'tag') {
                    $this->joins['tags'] = array(
                        'table' => 'photos_photo_tags',
                        'alias' => 'pt',
                    );
                    $tag_model = $this->getModel('tag');
                    if (strpos($parts[2], '||') !== false) {
                        $tags = explode('||', $parts[2]);
                        $tag_ids = $tag_model->getIds($tags);
                    } else {
                        $sql = "SELECT id FROM ".$tag_model->getTableName()." WHERE name".$this->getExpression($parts[1], $parts[2]);
                        $tag_ids = $tag_model->query($sql)->fetchAll(null, true);
                    }
                    if ($tag_ids) {
                        $this->where[] = "pt.tag_id IN ('".implode("', '", $tag_ids)."')";
                    }
                } elseif (substr($parts[0], 0, 5) == 'exif.') {
                    $this->joins['exif'] = array(
                        'table' => 'photos_photo_exif',
                        'alias' => 'exif',
                    );
                    $this->where[] = "(exif.name = '".$this->getModel()->escape(substr($parts[0], 5))."' AND exif.value".$this->getExpression($parts[1], $parts[2]).")";
                } elseif ($model->fieldExists($parts[0])) {
                    $title[] = $parts[0].$parts[1].$parts[2];
                    $this->where[] = 'p.'.$parts[0].$this->getExpression($parts[1], $parts[2]);
                }
            }
        }

        if ($title) {
            $title = implode(', ', $title);

            // Strip slashes from search title.
            $bs = '\\\\';
            $title = preg_replace("~{$bs}(_|%|&|{$bs})~", '\1', $title);
        }
        if ($auto_title) {
            $this->addTitle($title, ' ');
        }
    }


    /**
     * Returns photos in this collection.
     *
     * @param string|array $fields
     * @param int $offset
     * @param int $limit
     * @param bool $escape
     * @return array [photo_id][field] = field value in appropriate field format
     * @throws waException
     */
    public function getPhotos($fields = "*,thumb,tags", $offset = 0, $limit = 50, $escape = true)
    {
        $sql = $this->getSQL();
        $sql = "SELECT ".($this->joins ? 'DISTINCT ' : '').$this->getFields($fields)." ".$sql;
        //$sql .= $this->getGroupBy();
        $sql .= $this->getOrderBy();
        $sql .= " LIMIT ".($offset ? $offset.',' : '').(int)$limit;

        $data = $this->getModel()->query($sql)->fetchAll('id');
        if (!$data) {
            return array();
        }

        if ($this->post_fields) {
            $ids = array_keys($data);
            foreach ($this->post_fields as $table => $fields) {
                if ($table == '_internal') {
                    foreach ($fields as $i => $f) {
                        if ($f == 'thumb' || substr($f, 0, 6) == 'thumb_') {
                            if ($f == 'thumb') {
                                $size = photosPhoto::getThumbPhotoSize();
                            } else {
                                $size = substr($f, 6);
                                switch ($size) {
                                    case 'crop':
                                        $size = photosPhoto::getCropPhotoSize();
                                        break;
                                    case 'middle':
                                        $size = photosPhoto::getMiddlePhotoSize();
                                        break;;
                                    case 'big':
                                        $size = photosPhoto::getBigPhotoSize();
                                        break;
                                    case 'mobile':
                                        $size = photosPhoto::getMobilePhotoSize();
                                        break;
                                }
                            }
                            foreach ($data as $id => &$v) {
                                $v[$f] = photosPhoto::getThumbInfo($v, $size, false);
                            }
                            unset($v);
                        }
                        if ($f == 'frontend_link') {
                            foreach ($data as $id => &$v) {
                                $v['frontend_link'] = photosFrontendPhoto::getLink(array(
                                    'url' => $this->frontend_base_url ? $this->frontend_base_url.'/'.$v['url'] : $v['url']
                                ), null, false);
                            }
                            unset($v);
                        }
                        if ($f == 'edit_rights') {
                            $photo_model_rights = new photosPhotoRightsModel();
                            $photo_ids = array();
                            foreach ($data as $id => &$v) {
                                $photo_ids[] = $id;
                                $v['edit_rights'] = false;
                            }
                            unset($v);
                            foreach ($photo_model_rights->filterAllowedPhotoIds($photo_ids, true) as $photo_id) {
                                $data[$photo_id]['edit_rights'] = true;
                            }
                        }
                    }
                } elseif ($table == 'tags') {
                    $model = $this->getModel('photo_tags');
                    $tags = $model->getTags($ids);
                    foreach ($data as $id => &$v) {
                        $v['tags'] = isset($tags[$id]) ? $tags[$id] : array();
                    }
                    unset($v);
                }
            }
        }
        if ($escape) {
            self::escapePhotoFields($data);
        }
        return $data;
    }

    public static function extendPhotos($photos)
    {
        foreach ($photos as &$photo) {
            $photo['hooks'] = array(
                'thumb' => array(),
                'plain' => array(),
                'top_left' => array(),
                'top_right' => array(),
                'name' => array(),
            );
            $photo['upload_timestamp'] = strtotime($photo['upload_datetime']);
            unset($photo);
        }
        /**
         * Prepare photo data
         * Extend each photo item via plugins data
         * @event prepare_photos_frontend
         * @event prepare_photos_backend
         * @example public function preparePhotos(&$photos)
         * {
         *     foreach ($photos as &$photo) {
         *         $item['hooks']['thumb'][$this->id] = 'Extra info html code here';
         *     }
         * }
         * @param array[int][string]mixed $photos Post items
         * @param array[int][string]mixed $photos[id] Post item
         * @param array[int][string]int $photos[id]['id'] Post item ID
         * @param array[int][string][string]string $photos[id]['thumb'][%plugin_id%] Under main info of thumb
         * @param array[int][string][string]string $photos[id]['plain'][%plugin_id%] Under main info of plain view (frontend)
         * @param array[int][string][string]string $photos[id]['top_left'][%plugin_id%]
         * @param array[int][string][string]string $photos[id]['top_right'][%plugin_id%]
         * @return void
         */
        wa()->event('prepare_photos_'.wa()->getEnv(), $photos);
        return $photos;
    }


    public function count()
    {
        $sql = $this->getSQL();
        $sql = "SELECT COUNT(".($this->joins ? 'DISTINCT ' : '')."p.id) ".$sql;
        $count = (int)$this->getModel()->query($sql)->fetchField();
        if ($this->update_count && $count !== $this->update_count['count']) {
            $this->update_count['model']->updateCount($this->update_count['id'], $count);
            $this->update_count['count'] = $count;
        }
        return $count;
    }

    public function getPhotoOffset($photo)
    {
        $this->prepare();
        $order = explode(',', $this->order_by);

        if (count($order) == 3) {
            $this->where['_offset'] = '('.$this->getPhotoOffsetCondition($photo, $order[0]).' OR ('.
            $this->getPhotoOffsetCondition($photo, $order[0], true). ' AND '.$this->getPhotoOffsetCondition($photo, $order[1]).' ) OR ('.
                $this->getPhotoOffsetCondition($photo, $order[0], true). ' AND '.
                $this->getPhotoOffsetCondition($photo, $order[1], true). ' AND '.
                $this->getPhotoOffsetCondition($photo, $order[2]). '))';
        } else if (count($order) == 2) {
            $this->where['_offset'] = '('.$this->getPhotoOffsetCondition($photo, $order[0]).' OR ('.
            $this->getPhotoOffsetCondition($photo, $order[0], true). ' AND '.$this->getPhotoOffsetCondition($photo, $order[1]).' ))';
        } else {
            $this->where['_offset'] = $this->getPhotoOffsetCondition($photo, $order[0]);
        }
        $sql = "SELECT COUNT(".($this->joins ? 'DISTINCT ' : '')."p.id) ".$this->getSQL();

        // remove condition
        unset($this->where['_offset']);
        // return count
        return (int)$this->getModel()->query($sql)->fetchField();
    }

    private function getPhotoOffsetCondition($photo, $order, $eq = false)
    {
        $order = trim($order);
        $order = explode(' ', $order);
        if (!isset($order[1])) {
            $order[1] = 'asc';
        }

        $order[1] = strtolower($order[1]);

        list($t, $f) = explode('.', $order[0]);

        if ($t == 'ap' && $this->update_count) {
            $model = new photosAlbumPhotosModel();
            $row = $model->getByField(array(
                'album_id' => $this->update_count['id'],
                'photo_id' => $photo['id']
            ));
            $v = $row['sort'];
        } else {
            $v = $photo[$f];
        }
        // return condition
        return $order[0].($eq ? ' = ' : ($order[1] == 'asc' ? ' < ': ' > '))."'$v'";
    }


    /**
     * Returns ORDER BY clause
     * @return string
     */
    protected function getOrderBy()
    {
        if ($this->order_by) {
            return " ORDER BY ".$this->order_by;
        } else {
            return "";
        }
    }

    /**
     * Set order by clause for select
     *
     * @param string $field
     * @param string $order
     * @return string
     */
    public function orderBy($field, $order = 'ASC')
    {
        if (strtolower(trim($order)) == 'desc') {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }
        $field = trim($field);
        if ($field) {
            if (strpos($field, '.') === false) {
                $model = $this->getModel();
                if ($model->fieldExists($field)) {
                    return $this->order_by = 'p.'.$field." ".$order;
                }
            } else {
                return $this->order_by = $field.' '.$order;
            }
        }
        return '';
    }


    /**
     * Returns photos model
     *
     * @param string $type
     * @return photosPhotoModel|photosTagModel|photosPhotoTagsModel
     */
    protected function getModel($type = 'photo')
    {
        if (!isset($this->models[$type])) {
            if ($type == 'photo_tags') {
                $this->models[$type] = new photosPhotoTagsModel();
            } elseif ($type == 'tag') {
                $this->models[$type] = new photosTagModel();
            } elseif ($type == 'exif') {
                $this->models[$type] = new photosPhotoExifModel();
            } else {
                $this->models[$type] = new photosPhotoModel();
            }
        }
        return $this->models[$type];
    }


    public function getTitle()
    {
        if ($this->title === null) {
            $this->prepare();
        }
        return $this->title;
    }

    /** Add WHERE condition. Primarily for plugins that extend this collection. */
    public function addWhere($condition)
    {
        $this->where[] = $condition;
        return $this;
    }

    public function addTitle($title, $delim = ', ')
    {
        if (!$title) {
            return;
        }
        if ($this->title) {
            $this->title .= $delim;
        }
        $this->title .= $title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setCheckRights($check_rights)
    {
        $this->check_rights = $check_rights;
    }
}