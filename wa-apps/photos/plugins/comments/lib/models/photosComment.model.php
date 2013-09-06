<?php

class photosCommentModel extends waNestedSetModel
{
    const STATUS_DELETED	 = 'deleted';
    const STATUS_PUBLISHED	 = 'approved';

    const SMALL_AUTHOR_PHOTO_SIZE = 20;
    const BIG_AUTHOR_PHOTO_SIZE = 20;

    protected $table = 'photos_comment';

    public function getFullTree($photo_id, array $options = array())
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($photo_id) {
            $sql .= "WHERE photo_id = i:photo_id";
            if (wa()->getEnv() == 'frontend') {
                $sql .= " AND status = s:status";
            }
        }
        $sql .= " ORDER BY `left`";
        $tree = $this->query($sql, array(
            'photo_id' => $photo_id,
            'status' => self::STATUS_PUBLISHED
        ))->fetchAll();
        $this->extendItems($tree, $options);

        return $tree;
    }

    public function getCounters($datetime = null, $photo_id = null)
    {
        $where = array();
        $group_by = '';

        if (!$photo_id) {
            $sql = "SELECT COUNT(id) count FROM {$this->table} ";
        } else {
            $sql = "SELECT photo_id, COUNT(id) count FROM {$this->table} ";
            $cond = $this->getWhereByField('photo_id', $photo_id);
            if ($cond) {
                $where[] = $cond;
            }
            $group_by = 'photo_id';
        }

        $where[] = "status = '".self::STATUS_PUBLISHED."'";

        if ($datetime) {
            $datetime = $this->escape($datetime);
            $where[] = "datetime > '$datetime' ";
            $where[] = "contact_id != ".wa()->getUser()->getId();
        }

        $where = implode(' AND ', $where);
        if ($where) {
            $sql .= " WHERE $where";
        }
        if ($group_by) {
            $sql .= " GROUP BY $group_by";
        }
        if (!$photo_id) {
            return $this->query($sql)->fetchField('count');
        } else {
            return $this->query($sql)->fetchAll('photo_id', true);
        }
    }

    public function getList(array $options = array(), $offset, $count)
    {
        $offset = (int) $offset;
        $count = (int) $count;
        $sql = "SELECT * FROM {$this->table} ORDER BY datetime DESC LIMIT $offset, $count";
        $list = $this->query($sql, array(
            'offset' => $offset,
            'count' => $count
        ))->fetchAll('id');
        $this->extendItems($list, $options);
        return $list;
    }

    private function extendItems(&$items, array $options = array())
    {
        if (!empty($options)) {
            $options['author'] = isset($options['author']) ? $options['author'] : false;
            $options['crop'] = isset($options['crop']) ? $options['crop'] : false;
            $options['reply_to'] = isset($options['reply_to']) ? $options['reply_to'] : false;
            foreach ($items as &$item) {
                if ($options['author']) {
                    $author = array(
                        'name' =>  $item['name'],
                        'email' => $item['email'],
                        'site' =>  $item['site'],
                        'auth_provider' => $item['auth_provider']
                    );
                    $item['author'] = array_merge($author, self::getAuthorInfo($item['contact_id']));
                    if ($item['auth_provider'] && $item['auth_provider'] != 'guest') {
                        $item['author']['photo'] = self::getAuthProvoderIcon($item['auth_provider']);
                    }
                }
                if ($options['crop']) {
                    $item['crop'] = self::getPhotoCrop($item['photo_id']);
                }
                if ($options['reply_to']) {
                    $item['reply_to'] = null;
                    if (!$item['parent']) {
                        continue;
                    }
                    if (isset($items[$item['parent']])) {
                        // TODO: need trim to fix length text ?
                        $item['reply_to'] = $items[$item['parent']]['text'];
                    } else {
                        $parent = $this->getById($item['parent']);
                        $item['reply_to'] = $parent['text'];
                    }
                }
            }
            unset($item);
        }
    }

    static public function getAuthorInfo($id, $photo_size = self::SMALL_AUTHOR_PHOTO_SIZE)
    {
        static $authors_info = array();
        $id = max(0, intval($id));
        if (!isset($authors_info[$id][$photo_size])) {
            $contact = new waContact($id);
            $authors_info[$id][$photo_size] = array();
            $authors_info[$id][$photo_size]['id'] = $id;
            if ($id) {
                $authors_info[$id][$photo_size]['name'] = $contact->getName();
            }
            $authors_info[$id][$photo_size]['photo'] = $contact->getPhoto($photo_size);
        }

        return $authors_info[$id][$photo_size];
    }

    static public function getAuthProvoderIcon($provider)
    {
        return wa()->getRootUrl().'wa-content/img/auth/'.$provider.'.png';
    }

    static public function getPhotoCrop($photo_id)
    {
        static $photos = array();
        static $photo_model = null;

        $photo_model = !is_null($photo_model) ? $photo_model : new photosPhotoModel();
        if (!isset($photos[$photo_id])) {
            $photo = $photo_model->getById($photo_id);
            $photos[$photo_id] = photosPhoto::getPhotoUrl($photo, photosPhoto::getCropPhotoSize());
        }
        return $photos[$photo_id];
    }

    public function add($comment, $parent = null, $before_id = null)
    {
        if (!isset($comment['datetime'])) {
            $comment['datetime'] = date('Y-m-d H:i:s');
        }
        if (isset($comment['site']) && $comment['site']) {
            if (!preg_match('@^https?://@',$comment['site'])) {
                $comment['site'] = 'http://'.$comment['site'];
            }
        }
        $before_id = null;
        return parent::add($comment, $parent, $before_id);
    }



    public function validate($comment)
    {
        $errors = array();

        if($comment['contact_id']) {
            $user = wa()->getUser();
            if ($user->getId() && !$user->get('is_user')) {
                $user->addToCategory(wa()->getApp());
            }
        } elseif ($comment['auth_provider'] == 'guest') {
            if (!empty($comment['site']) && strpos($comment['site'], '://')===false) {
                $comment['site'] = "http://" . $comment['site'];
            }

            if (empty($comment['name']) || (mb_strlen( $comment['name'] ) == 0) ) {
                $errors[]['name'] = _wp('Name can not be left blank');
            }
            if (mb_strlen( $comment['name'] ) > 255) {
                $errors[]['name'] = _wp('Name length should not exceed 255 symbols');
            }
            if (empty($comment['name']) || (mb_strlen( $comment['email'] ) == 0) ) {
                $errors[]['email'] = _wp('Email can not be left blank');
            }
            $validator = new waEmailValidator();
            if (!$validator->isValid($comment['email'])) {
                $errors[]['email'] = _wp('Email is not valid');
            }
            $validator = new waUrlValidator();
            if (!empty($comment['site']) && !$validator->isValid($comment['site'])) {
                $errors[]['site'] = _wp('Site URL is not valid');
            }
            if (!wa()->getUser()->isAuth() && !wa()->getCaptcha()->isValid()) {
                $errors[] = array('captcha' => _wp('Invalid captcha code'));
            }
        } else {
            $auth_adapters = wa()->getAuthAdapters();
            if (!isset($auth_adapters[$comment['auth_provider']])) {
                $errors[] = _w('Invalid auth provider');
            }
        }

        if (mb_strlen($comment['text']) == 0) {
            $errors[]['text'] = _wp('Comment text can not be left blank');
        }
        if (mb_strlen($comment['text']) > 4096) {
            $errors[]['text'] = _wp('Comment length should not exceed 4096 symbols');
        }
        return $errors;
    }

}