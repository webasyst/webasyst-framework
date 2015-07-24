<?php
/**
 *
 * @author Webasyst
 *
 */

class stickiesSheetActions extends stickiesJsonActionsController
{
    /**
     *
     * @var stickiesSheetModel
     */
    private $sheet_model;

    /**
     *
     * @var int
     */
    private $sheet_id;

    public function preExecute()
    {
        parent::preExecute();
        $this->sheet_model = new stickiesSheetModel();
        $this->sheet_id = (int)waRequest::post('sheet_id');
        // Check user rights
        //if (!waSystem::getInstance()->getUser()->getRights(waSystem::getInstance()->getApp(), 'state.'.$state_id))
        //	$this->response = '#/orders/all/'; // return redirect url in response
    }

    protected function listAction()
    {
        //mobile version
        $this->response = array();

        $this->response['sheets'] = $this->sheet_model->get(false,array('id','name','qty'));
    }

    protected function defaultAction()
    {
        $default_sheet_id = null;
        $this->response = array();
        $this->response['current_sheet_id'] = false;
        $this->response['default_sheet_id'] = false;

        $this->response['sheets'] = $this->sheet_model->get();
        foreach ($this->response['sheets'] as $sheet) {
            if (!$this->sheet_id) {
                $this->response['current_sheet_id'] = (int)$sheet['id'];
                $this->response['current_sheet'] = $sheet;
                break;
            }
            if (!$this->response['default_sheet_id']) {
                $this->response['default_sheet_id'] = (int)$sheet['id'];
            }
            if ($sheet['id'] == $this->sheet_id) {
                $this->response['current_sheet_id'] = $this->sheet_id;
                $this->response['current_sheet'] = $sheet;
                $this->response['current_sheet_add'] = ( $sheet['create_datetime'] > date("Y-m-d H:i:s", strtotime('-10 sec')) );
                break;
            }
        }

        if ($this->response['current_sheet_id']) {
            $stickies_model = new stickiesStickyModel();
            $this->response['stickies'] = $stickies_model->getBySheetId($this->response['current_sheet_id']);

        } else {
            $this->response['stickies'] = array();
        }
    }

    protected function viewAction()
    {
        //mobile version
        $this->response = array();
        if ($this->sheet_id) {
            $stickies_model = new stickiesStickyModel();
            $this->response['stickies'] = $stickies_model->getBySheetId($this->sheet_id,array('id','content','color'));
            foreach ($this->response['stickies'] as &$sticky) {
                if (strlen($sticky['content'])>120){
                    $sticky['content'] = mb_substr($sticky['content'],0,80,'utf-8').'...';
                }
            }
            unset($sticky);
            $this->response['current_sheet'] = $this->sheet_model->getById($this->sheet_id);

        } else {
            $this->response['stickies'] = array();
        }
    }
    protected function addAction()
    {
        if ($sheet_id=$this->sheet_model->create(_w('Board'))) {
            $this->response = $this->sheet_model->getById($sheet_id);
            $this->log('board_add', 1);
        } else {
            $this->errors = _w("Not enough rights to add new board");
        }
    }

    protected function saveAction()
    {
        if ($this->sheet_model->available($this->sheet_id)) {
            $sheet_data = array(
				'name' => waRequest::post('name', '', 'string_trim'),
				'background_id' => waRequest::post('background_id', '', 'string_trim'),
            );
            $this->sheet_model->updateById($this->sheet_id, $sheet_data);
            $this->response = $sheet_data;
            $this->log('board_edit', 1);
        } else {
            $this->errors = _w("Not enough rights to work with current board");
        }
    }

    protected function sortAction()
    {
        $id = (int)waRequest::post('id');
        $after_id = (int)waRequest::post('after_id');
        $this->response = $this->sheet_model->move($id, $after_id);
    }

    protected function deleteAction()
    {
        if ($this->sheet_model->deleteById($this->sheet_id)) {
            $this->response['sheet_id'] = $this->sheet_id;

            // Delete rights
            $right_model = new waContactRightsModel();
            $right_model->deleteByField(array('app_id'=>wa()->getApp(), 'name'=>'sheet.'.$this->sheet_id));

            $this->log('board_delete', 1);
        } else {
            $this->errors = _w("Not enough rights to work with current board");
        }
    }
}
