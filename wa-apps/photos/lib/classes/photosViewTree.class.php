<?php

class photosViewTree
{
    protected $elements = array();
    protected $childs = array();

    public function __construct($elements)
    {
        foreach ($elements as $element) {
            $this->add($element);
        }
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
    /**
     * @var photosViewTreeElement
     */
    protected $parent;
    public function __construct($data, &$tree)
    {
        $this->tree = $tree;
        $this->setData($data);
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
        switch ($this->data['type']) {
            case photosAlbumModel::TYPE_STATIC:
                return 'pictures';
            case photosAlbumModel::TYPE_DYNAMIC:
                return 'funnel';

        }
        return $this->data['type'];
    }

    protected function getStatusIcon()
    {
        if ($this->data['status'] <= 0) {
            return '<i class="icon10 lock-bw no-overhanging"></i>';
        }
        return '';
    }

    protected function getClass()
    {
        switch ($this->data['type']) {
            case photosAlbumModel::TYPE_STATIC:
                return 'static ';
            case photosAlbumModel::TYPE_DYNAMIC:
                return 'dynamic ';
        }
        return $this->data['type'];
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
        return $result;
    }
}