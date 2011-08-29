<?php

/**
 * Collection of backend actions that output JSON data
 */
class checklistsJsonActions extends waJsonActions
{
    /** Default action when no other action is specified. */
    public function defaultAction()
    {
        throw new waException('Unknown action.');
    }

    /** Save list item using POST data from list page */
    public function ItemsaveAction()
    {
        $item = array();
        foreach(array('list_id', 'name', 'sort') as $k) {
            $v = waRequest::post($k, null);
            if ($v !== null) {
                $item[$k] = $v;
            }
        }

        if (waRequest::post('check')) {
            $item['contact_id'] = wa()->getUser()->getId();
            $item['done'] = date('Y-m-d H:i:s');
        } else if (waRequest::post('uncheck')) {
            $item['contact_id'] = null;
            $item['done'] = null;
        }

        $id = waRequest::post('id', 0, 'int');
        $lim = new checklistsListItemsModel();
        if ($id) {
            unset($item['list_id']);
            if (isset($item['name']) && strlen($item['name']) <= 0) {
                unset($item['name']);
            }

            $i = $lim->getById($id);

            // check access
            $access = $this->getRights('list.'.$i['list_id']);
            if (!$access || ($access <= 1 && isset($item['name']))) {
                throw new waRightsException('Access denied.');
            }

            $lim->moveApart($i['list_id'], isset($item['sort']) ? $item['sort'] : $i['sort']);
            $lim->updateById($id, $item);

            // update log
            if (isset($item['name'])) {
                $this->log('item_edit', 1);
            }
            if (array_key_exists('done', $item)) {
                if ($item['done']) {
                    $this->log('item_check', 1);
                } else {
                    $this->log('item_uncheck', 1);
                }
            }
        } else {
            if(!isset($item['name']) || strlen($item['name']) <= 0 || empty($item['list_id'])) {
                throw new waException('Not enough parameters.');
            }

            // check access
            $access = $this->getRights('list.'.$item['list_id']);
            if ($access <= 1) {
                throw new waRightsException('Access denied.');
            }

            $lim->moveApart($item['list_id'], isset($item['sort']) ? $item['sort'] : 0);
            $id = $lim->insert($item);
            $this->log('item_create', 1);
        }

        $this->response = checklistsItem::prepareItem($lim->getById($id));

        $lm = new checklistsListModel();
        $lm->updateCount($this->response['list_id']);
    }

    /** Move list in sidebar */
    public function ListmoveAction()
    {
        if (! ( $id = waRequest::post('id', 0, 'int'))) {
            throw new waException('No id specified.');
        }
        if (null === ( $sort = waRequest::post('sort', null, 'int'))) {
            throw new waException('No sort specified.');
        }
        if(!$this->getRights('add_list')) {
            throw new waRightsException('Access denied.');
        }

        $lm = new checklistsListModel();
        $lm->moveApart($sort);
        $lm->updateById($id, array('sort' => $sort));
    }

    /** Save list using POST data from list settings form */
    public function ListsaveAction()
    {
        $list = array(
            'name' => waRequest::post('name', ''),
            'color_class' => waRequest::post('color_class', 'c-yellow'),
            'icon' => waRequest::post('icon', 'notebook'),
        );

        if(strlen($list['name']) <= 0) {
            throw new waException('No name specified.');
        }

        $id = waRequest::post('id', 0, 'int');
        $lm = new checklistsListModel();
        if ($id) {
            if($this->getRights('list.'.$id) <= 1) {
                throw new waRightsException('Access denied.');
            }
            $lm->updateById($id, $list);
        } else {
            if(!$this->getRights('add_list')) {
                throw new waRightsException('Access denied.');
            }
            $lm->moveApart(0);
            $id = $lm->insert($list);

            // if user is not an admin then grant him full access on newly created list
            $admin = wa()->getUser()->getRights('checklists', 'backend') > 1;
            $rm = new waContactRightsModel();
            if (!$admin) {
                $rm->save(wa()->getUser()->getId(), 'checklists', 'list.'.$id, 2);
            }
            $this->log('list_create', 1);
        }
        $this->response = $id;
    }

    /** Delete item */
    public function DeleteitemAction()
    {
        if (! ( $id = waRequest::post('id', 0, 'int'))) {
            throw new waException('No id given.');
        }

        $lim = new checklistsListItemsModel();
        if (! ( $item = $lim->getById($id))) {
            return;
        }

        // check access
        if($this->getRights('list.'.$item['list_id']) <= 1) {
            throw new waRightsException('Access denied.');
        }

        $lim->deleteById($id);
        $lm = new checklistsListModel();
        $lm->updateCount($item['list_id']);
        $this->response = 'done';
        $this->log('item_delete', 1);
    }

    /** Start over by unchecking all list items */
    public function StartoverAction()
    {
        if (! ( $id = waRequest::post('id', 0, 'int'))) {
            throw new waException('No id given.');
        }
        if(!$this->getRights('list.'.$id)) {
            throw new waRightsException('Access denied.');
        }

        $lim = new checklistsListItemsModel();
        $lim->updateByField('list_id', $id, array('done' => null));

        $lm = new checklistsListModel();
        $lm->updateCount($id);

        $this->response = checklistsItem::prepareItems(array_values($lim->getByList($id)));
        $this->log('list_startover', 1);
    }

    /** Delete list */
    public function DeletelistAction()
    {
        if (! ( $id = waRequest::post('id', 0, 'int'))) {
            throw new waException('No id given.');
        }

        // check access
        if($this->getRights('list.'.$id) <= 1) {
            throw new waRightsException('Access denied.');
        }

        $lm = new checklistsListModel();
        $lm->deleteById($id);

        $lim = new checklistsListItemsModel();
        $lim->deleteByField('list_id', $id);

        $this->log('list_delete', 1);
        $this->response = 'done';
    }
}

