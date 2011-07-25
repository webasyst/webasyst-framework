<?php

/**
 * Collection of backend actions that show HTML pages
 */
class checklistsBackendActions extends waViewActions
{
    public function __construct(waSystem $system = null)
    {
        parent::__construct($system);
        $this->setLayout(new checklistsDefaultLayout());
    }

    /** Default action when no other action is specified. */
    public function defaultAction()
    {
        $lm = new checklistsListModel();
        $lists = $lm->getAllowed();
        if (!$lists) {
            if ($this->getRights('add_list')) {
                $this->execute('editor');
                return;
            }

            // No available lists and cannot create new one: show default template
            return;
        }

        // is there a cookie with last list user opened?
        $id = waRequest::cookie('last_list_id', 0, 'int');
        if ($id && isset($lists[$id])) {
            $this->execute('list', $lists[$id]);
            return;
        }

        // simply show the first list
        $lists = array_values($lists);
        $this->execute('list', $lists[0]);
    }

    /** Show items in TODO list */
    public function ListAction($list = null)
    {
        if (!$list) {
            if (! ( $id = waRequest::request('id', 0, 'int'))) {
                throw new waException('No id specified.');
            }
            $lm = new checklistsListModel();
            if (! ( $list = $lm->getById($id))) {
                throw new waException('List does not exist.');
            }
        }

        $access = $this->getRights('list.'.$list['id']);
        if (!$access) {
            throw new waRightsException('Access denied.');
        }
        $this->view->assign('can_edit', $access > 1);
        $this->view->assign('list', $list);

        $lim = new checklistsListItemsModel();
        $items = checklistsItem::prepareItems($lim->getByList($list['id']));

        $this->view->assign('items', array_values($items));

        wa()->getResponse()->setCookie('last_list_id', $list['id']);
        $this->layout->setTitle($list['name']);
    }

    /** Create new or edit existing list. */
    public function EditorAction()
    {
        $id = waRequest::request('id', 0, 'int');
        if ($id) {
            if($this->getRights('list.'.$id) <= 1) {
                throw new waRightsException('Access denied.');
            }
            $lm = new checklistsListModel();
            if (! ( $list = $lm->getById($id))) {
                throw new waException('List does not exist.');
            }
            $this->layout->setTitle($list['name']);
        } else {
            if(!$this->getRights('add_list')) {
                throw new waRightsException('Access denied.');
            }
            $list = array(
                'id' => '',
                'name' => '',
                'color_class' => 'c-white',
                'icon' => 'notebook',
                'count' => 0,
            );
        }
        $this->view->assign('list', $list);

        $this->view->assign('icons', array(
            'notebook',
            'lock',
            'lock-unlocked',
            'broom',
            'star',
            'livejournal',
            'contact',
            'lightning',
            'light-bulb',
            'pictures',
            'reports',
            'books',
            'marker',
            'lens',
            'alarm-clock',
            'animal-monkey',
            'anchor',
            'bean',
            'car',
            'disk',
            'cookie',
            'burn',
            'clapperboard',
            'bug',
            'clock',
            'cup',
            'home',
            'fruit',
            'luggage',
            'guitar',
            'smiley',
            'sport-soccer',
            'target',
            'medal',
            'phone',
            'store',
        ));

        $this->view->assign('colors', array(
            'c-white',
            'c-gray',
            'c-yellow',
            'c-green',
            'c-blue',
            'c-red',
            'c-purple',
        ));
    }
}

