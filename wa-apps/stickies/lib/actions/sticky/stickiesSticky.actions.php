<?php
class StickiesStickyActions extends stickiesJsonActionsController
{
	/**
	 *
	 * @var stickiesStickyModel
	 */
	private $sticky_model;

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

	/**
	 *
	 * @var int
	 */
	private $sticky_id;

	public function preExecute()
	{
		parent::preExecute();
		$this->sheet_model = new stickiesSheetModel();
		$this->sheet_id = (int)waRequest::post('sheet_id');

		$this->sticky_model = new stickiesStickyModel();
		$this->sticky_id = (int)waRequest::post('id');
	}

	protected function viewAction()
	{
		$items = $this->sticky_model->getFieldsByField(array('id'=>$this->sticky_id));
		$sticky = array_shift($items);
		$this->sticky_model->available($sticky['id']);

		$sheet = $this->sheet_model->getById($sticky['sheet_id']);

		$this->response = array(
			'sticky'=>$sticky,
			'sheet'=>$sheet,
		);
	}

	protected function addAction()
	{
		$sticky_data = array(
			'position_top'=>2000+rand(0,1000),
			'position_left'=>2000+rand(0,1000),
			'content'=>'',
			'color'=>'default',
			'size_height'=>150,
			'size_width'=>150,
			'sheet_id'=>$this->sheet_id,
		);

		$this->response = $this->sticky_model->create($this->sheet_id,$sticky_data);;
		$this->log('sticky_add', 1);
	}

	protected function deleteAction()
	{
		if($this->sticky_model->delete($this->sticky_id)){
			$this->response = array('id'=>$this->sticky_id);
			$this->log('sticky_delete', 1);
		}else{
			$this->errors = _w("Error while delete sticky");
		}
	}

	protected function modifyAction()
	{
		if($this->sticky_id){
			$data = array();
			$fields = array(
				'content'		 =>waRequest::TYPE_STRING,
				'size'			 =>waRequest::TYPE_INT,
				'color'			 =>waRequest::TYPE_STRING,
				'font_size'		 =>waRequest::TYPE_INT,
				'position_top'	 =>waRequest::TYPE_INT,
				'position_left'	 =>waRequest::TYPE_INT,
				'size_width'	 =>waRequest::TYPE_INT,
				'size_height'	 =>waRequest::TYPE_INT,
				'sheet_id'	 =>waRequest::TYPE_INT,
			);
			foreach($fields as $field => $type){
				$value = waRequest::post($field, false, $type);
				if($value !== false){
					$data[$field] = $value;
				}
			}
			if(isset($data['size'])&&$data['size']){
				$data['size_height'] = $data['size_width'] = $data['size'];
				unset($data['size']);
			}

			$sticky = $this->sticky_model->getById($this->sticky_id);
			if ($sticky) {
//				if ( isset($data['size_width']) && $data['size_width'] != $sticky['size_width'] ||
//					isset($data['size_height']) && $data['size_height'] != $sticky['size_height'])
//				{
//					$this->log('sticky_resize', 1);
//				}
//				if ( isset($data['position_top']) && $data['position_top'] != $sticky['position_top'] ||
//					isset($data['position_left']) && $data['position_left'] != $sticky['position_left'])
//				{
//					$this->log('sticky_move', 1);
//				}
				if (isset($data['sheet_id']) && $data['sheet_id'] != $sticky['sheet_id']) {
					$this->log('sticky_move_to_board', 1);
				}
				if (isset($data['content']) && $data['content'] != $sticky['content']) {
					$this->log('sticky_edit', 1);
				}
			}

			if($res = $this->sticky_model->modify($this->sticky_id,$data)){
				$data['id'] = $this->sticky_id;
				$this->response = $data;
			}else{
				$this->response = $res;
				$this->errors = _w('Error while save sticky');
			}
		}else{
			$this->errors = _w('Invalid sticky ID');
		}
	}
}
