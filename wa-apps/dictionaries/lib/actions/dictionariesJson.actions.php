<?php

/**
 * Collection of backend actions that output JSON data
 */
class dictionariesJsonActions extends waJsonActions {
    /** Default action when no other action is specified. */
    public function defaultAction()
    {
        throw new waException('Unknown action.');
    }

    /** Save list item using POST data from list page */
    public function ItemsaveAction()
    {
        $item = array();
        foreach(array('dictionary_id', 'name', 'value', 'description', 'visible', 'sort') as $k) {
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
        $lim = new dictionariesItemsModel();
        if ($id) {
            unset($item['dictionary_id']);
            if (isset($item['name']) && strlen($item['name']) <= 0) {
                unset($item['name']);
            }

            $i = $lim->getById($id);

            // check access
            $access = $this->getRights('list.'.$i['dictionary_id']);
            if (!$access || ($access <= 1 && isset($item['name']))) {
                throw new waRightsException('Access denied.');
            }

            $lim->moveApart($i['dictionary_id'], isset($item['sort']) ? $item['sort'] : $i['sort']);
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
            if(!isset($item['name']) || strlen($item['name']) <= 0 || empty($item['dictionary_id'])) {
                throw new waException('Not enough parameters.');
            }

            // check access
            $access = $this->getRights('list.'.$item['dictionary_id']);
            if ($access <= 1) {
                throw new waRightsException('Access denied.');
            }

            $lim->moveApart($item['dictionary_id'], isset($item['sort']) ? $item['sort'] : 0);
            $id = $lim->insert($item);
            $this->log('item_create', 1);
        }

        $this->response = dictionariesItem::prepareItem($lim->getById($id));

        $lm = new dictionariesModel();
        $lm->updateCount($this->response['dictionary_id']);
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

        $lm = new dictionariesModel();
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
        $lm = new dictionariesModel();
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
            $admin = wa()->getUser()->getRights('dictionaries', 'backend') > 1;
            $rm = new waContactRightsModel();
            if (!$admin) {
                $rm->save(wa()->getUser()->getId(), 'dictionaries', 'list.'.$id, 2);
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

        $lim = new dictionariesItemsModel();
        if (! ( $item = $lim->getById($id))) {
            return;
        }

        // check access
        if($this->getRights('list.'.$item['dictionary_id']) <= 1) {
            throw new waRightsException('Access denied.');
        }

        $lim->deleteById($id);
        $lm = new dictionariesModel();
        $lm->updateCount($item['dictionary_id']);
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

        $lim = new dictionariesItemsModel();
        $lim->updateByField('dictionary_id', $id, array('done' => null));

        $lm = new dictionariesModel();
        $lm->updateCount($id);

        $this->response = dictionariesItem::prepareItems(array_values($lim->getByList($id)));
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

        $lm = new dictionariesModel();
        $lm->deleteById($id);

        $lim = new dictionariesItemsModel();
        $lim->deleteByField('dictionary_id', $id);

        $this->log('list_delete', 1);
        $this->response = 'done';
    }



    public function getrowsAction() {

        if (! ( $id = waRequest::get('id', 0, 'int'))) {
            throw new waException('No id given.');
        }

        $lim = new dictionariesItemsModel();

        $callbackParams['page'] = waRequest::get('page', 1, 'int'); // get the requested page
        $callbackParams['limit'] = waRequest::get('rows', 100, 'int'); // get how many rows we want to have into the grid
        $callbackParams['sidx'] = waRequest::get('sidx', 'name', 'string'); // get index row - i.e. user click to sort
        $callbackParams['sord'] = waRequest::get('sord', 'asc', 'string'); // get the direction

	$callbackParams['search'] = waRequest::get('_search', 'false', 'string');

	if ($callbackParams['search'] == 'true') {
		$callbackParams['searchField'] = waRequest::get('searchField', 'name', 'string');
		$callbackParams['searchString'] = waRequest::get('searchString', '', 'string');
	}


        if(strlen($callbackParams['sidx'])<1) $callbackParams['sidx'] ='name';
	if(strlen($callbackParams['sord'])<1) $callbackParams['sord'] ='asc';


	$count = $lim->getCountByList($id);
	$count = $count['count'];



        if( $count >0 ) {
            $total_pages = ceil($count/$callbackParams['limit']);
        } else {
            $total_pages = 0;
        }
        if ($callbackParams['page'] > $total_pages) $callbackParams['page']=$total_pages;

        $callbackParams['start'] = (($callbackParams['limit']*$callbackParams['page'] - $callbackParams['limit'])>=0)?($callbackParams['limit']*$callbackParams['page'] - $callbackParams['limit']):0; // do not put $limit*($page - 1)

	$result = $lim->getSortedByList($id, $callbackParams);

        $responce['page'] = $callbackParams['page'];
        $responce['total'] = $total_pages;
        $responce['records'] = $count;

	foreach ($result AS $i => $row) {
		$responce['rows'][$i]['id']=$row['id'];
		$responce['rows'][$i]['cell']=array($row['name'],$row['value'],$row['description'],$row['visible'],$row['sort']);
	}

	$this->response = $responce;

    }

    public function editrowsAction()
    {
	$lim = new dictionariesItemsModel();

	$id = waRequest::post('id', '', 'string');
	$oper = waRequest::post('oper', '', 'string');

	$row['name'] = waRequest::post('name', '', 'string');
	$row['value'] = waRequest::post('value', '', 'string');
	$row['description'] = waRequest::post('description', '', 'string');
	$row['visible'] = waRequest::post('visible', 1, 'int');
	$row['sort'] = waRequest::post('sort', 0, 'int');

	if (isset($id) && $id != "new_row" && $oper!='del') {
		$lim->updateById($id, $row);
	}
	elseif ($oper=='del') {
//var_dump($id);
//exit;
		$this->DeleteitemAction();
	}
	else {
		$row['dictionary_id'] = waRequest::get('id', 0, 'int');
		$lim->insert($row);
	}
	
    }

}

