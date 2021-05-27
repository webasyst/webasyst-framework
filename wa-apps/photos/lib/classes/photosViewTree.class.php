<?php

class photosViewTree
{
    protected $elements = array();
    protected $childs = array();
    protected $ui;

    public function __construct($elements)
    {
        foreach ($elements as $element) {
            $this->add($element);
        }
        $this->ui = wa()->whichUI();
    }

    public function add($element)
    {
        $e = $this->getById($element['id']);
        $e->setData($element);
        if (!$e->getParent()) {
            $this->childs[] = $e;
        }
    }

    /**
     * Returns tree element by id
     *
     * @param int $id
     * @return photosViewTreeElement
     */
    public function getById($id)
    {
        if (!isset($this->elements[$id])) {
            $this->elements[$id] = new photosViewTreeElement(array('id' => $id), $this);
        }
        return $this->elements[$id];
    }

    public function display($view_type = 'backend')
    {
        if (empty($this->elements)) {
            return '';
        }
        $result = $view_type == 'backend' ?
                    '<ul class="menu-v with-icons"><li class="drag-newposition"></li>' :
                    '<ul class="menu-v album-tree">';
        if ($this->ui === '2.0') {
            $result = $view_type == 'backend' ?
                '<ul class="menu ellipsis nested js-root-menu">' :
                '<ul class="menu ellipsis album-tree">';
        }
        foreach ($this->childs as $e) {
            $result .= $e->display($view_type);
        }
        $result .= '</ul>';
        return $result;
    }
}

class photosViewTreeElement
{
    /**
     * @var contactsViewTree
     */
    protected $tree;
    protected $childs = array();
    protected $data;
    protected $ui;
    /**
     * @var photosViewTreeElement
     */
    protected $parent;
    public function __construct($data, &$tree)
    {
        $this->tree = $tree;
        $this->setData($data);
        $this->ui = wa()->whichUI();
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function add(&$e)
    {
        $this->childs[] = $e;
    }

    public function setData($data)
    {
        $this->data = $data;
        if (isset($data['parent_id']) && $data['parent_id']) {
            $this->parent = $this->tree->getById($data['parent_id']);
            $this->parent->add($this);
        }
    }

    protected function getHash()
    {
        return '#/album/'.$this->data['id'].'/';
    }

    protected function getIcon()
    {
        if ($this->ui === '2.0') {
            switch ($this->data['type']) {
                case photosAlbumModel::TYPE_STATIC:
                    return 'images';
                case photosAlbumModel::TYPE_DYNAMIC:
                    return 'filter';

            }
        }else{
            switch ($this->data['type']) {
                case photosAlbumModel::TYPE_STATIC:
                    return 'pictures';
                case photosAlbumModel::TYPE_DYNAMIC:
                    return 'funnel';

            }
        }
        return $this->data['type'];
    }

    protected function getStatusIcon()
    {
        if ($this->data['status'] <= 0) {
            if ($this->ui === '2.0') {
                return '<span class="hint"><i class="fas fa-lock"></i></span>&nbsp;';
            }
            return '<i class="icon10 lock-bw no-overhanging"></i>';
        }
        return '';
    }

    protected function getClass()
    {
        if ($this->ui === '2.0') {
            switch ($this->data['type']) {
                case photosAlbumModel::TYPE_STATIC:
                    return 'images';
                case photosAlbumModel::TYPE_DYNAMIC:
                    return 'filter';

            }
        }else{
            switch ($this->data['type']) {
                case photosAlbumModel::TYPE_STATIC:
                    return 'static ';
                case photosAlbumModel::TYPE_DYNAMIC:
                    return 'dynamic ';
            }
        }

        return $this->data['type'];
    }

    private function getPhotoThumb($id, $size = null)
    {
        $photo_model = new photosPhotoModel();
        if ($photo = $photo_model->getById($id)) {
            $path = photosPhoto::getPhotoPath($photo);
            if(file_exists($path)) {
                $size = !is_null($size) ? $size : photosPhoto::getThumbPhotoSize();
                $sizes = array();
                foreach (explode(',', $size) as $s) {
                    $sizes[] = 'thumb_'.trim($s);
                }

                $collection = new photosCollection("id/{$id}");

                $fields = "*";
                if ($sizes) {
                    $fields .= "," . implode(',', $sizes);
                }

                return $collection->getPhotos($fields, 0, 1, true);
            }
        }
        return null;
    }

    public function display($view_type = 'backend')
    {
        $result = $view_type == 'backend' ?
            '<li class="dr '.$this->getClass().'" rel="'.$this->data['id'].'"><span class="count">'.(!is_null($this->data['count']) ? $this->data['count'] : '').'</span>' :
            '<li>';

        if ($this->childs) {
            $result .= $view_type == 'backend' ?
                '<i class="icon16 darr overhanging collapse-handler" id="album-'.$this->data['id'].'-handler"></i>' :
                '';
        }
        $result .= $view_type == 'backend' ?
            '<a href="'.$this->getHash().'"><i class="icon16 '.$this->getIcon().'"></i><span class="album-name">'.photosPhoto::escape(ifempty($this->data['name'], _w('(no name)'))).'</span> '.$this->getStatusIcon().
            ' <strong class="small highlighted count-new">'.(!empty($this->data['count_new']) ? '+' . $this->data['count_new'] : '').'</strong>'.
            ' <span class="count"><i class="icon10 add p-new-album"></i></span>'.
            '</a>' :
            '<a href="'.photosFrontendAlbum::getLink($this->data).'">'.photosPhoto::escape($this->data['name']).'</a>';
        if ($this->childs) {
            $result .= $view_type == 'backend' ?
                '<ul class="menu-v with-icons"><li class="drag-newposition"></li>' :
                '<ul class="menu-v">';
            foreach ($this->childs as $e) {
                $result .= $e->display($view_type);
            }
            $result .= '</ul>';
        }
        $result .= $view_type == 'backend' ?
            '</li><li class="drag-newposition"></li>' :
            '</li>';

        // For WA2 UI
        if ($this->ui === '2.0') {
            $key_photo = '<i class="fas fa-'.$this->getIcon().'"></i>';
            $key_photo_id = ifempty($this->data['key_photo_id'], '');
            if($key_photo_id){
                $key_photo_40 = $this->getPhotoThumb($this->data['key_photo_id'], '40x40@2x');
                if ($key_photo_40) {
                    $key_photo_thumb = $key_photo_40[$key_photo_id]['thumb_40x40@2x']['url'];
                    $key_photo = '<span class="icon key-photo"><img class="size-20" src="'.$key_photo_thumb.'" alt=""></span>';
                }
            }

            $result = $view_type == 'backend' ?
                '<li class="'.$this->getClass().'" data-id="'.$this->data['id'].'" rel="'.$this->data['id'].'">' :
                '<li>';

            $result .= $view_type == 'backend' ?
                '<a href="'.$this->getHash().'" title="'.photosPhoto::escape(ifempty($this->data['name'], _w('(no name)'))).'">' : '';
                if ($this->childs) {
                    $result .= $view_type == 'backend' ? '<span class="caret" title=""><i class="fas fa-caret-down" id="album-'.$this->data['id'].'-handler" title=""></i></span>' : '';
                }
                $result .= $view_type == 'backend' ? '<span class="count">'.$this->getStatusIcon().(!is_null($this->data['count']) ? $this->data['count'] : '').'</span>'.
                $key_photo.
                '<span class="album-name">'.photosPhoto::escape(ifempty($this->data['name'], _w('(no name)'))).'</span> '.
                ' <strong class="small highlighted count-new">'.(!empty($this->data['count_new']) ? '+' . $this->data['count_new'] : '').'</strong>'.
                ' <span class="count action p-new-album small" title="'. _w('New album').'"><i class="fas fa-plus-circle"></i></span>'.
                '</a>' :
                '<a href="'.photosFrontendAlbum::getLink($this->data).'">'.photosPhoto::escape($this->data['name']).'</a>';
                if ($this->getClass() === "filter") {
                    $result .= '<ul class="menu ellipsis">';
                }else{
                    $result .= '<ul class="menu ellipsis nested">';
                }
                if ($this->childs) {

                    foreach ($this->childs as $e) {
                        $result .= $e->display($view_type);
                    }
                }
            $result .= '</ul>';
            $result .= '</li>';
        }
        return $result;
    }
}
