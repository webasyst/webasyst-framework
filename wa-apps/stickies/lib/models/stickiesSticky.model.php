<?php
/**
 *
 * @author Webasyst
 * @package Stickies
 *
 */
class stickiesStickyModel extends stickiesModel
{
	protected $table = 'stickies_sticky';

	/**
	 *
	 * @param $sheet_id int
	 * @param $fields array|string
	 * @return array
	 */
	public function getBySheetId($sheet_id, $fields = null)
	{
		return $this->getFieldsByField(array('sheet_id'=>$sheet_id),$fields,array('update_datetime'=>'asc'));
	}

	/**
	 * return sheet_id if at available or false
	 * @param $id int
	 * @return int
	 */
	public function available($id)
	{
		$sheet_id = 0;
		$data = $this->getFieldsByField(array('id'=>$id),array('sheet_id'));
		$row = array_shift($data);
		if(is_array($row)&&isset($row['sheet_id'])){
			$sheet_id = intval($row['sheet_id']);
		}
		self::availableSheet($sheet_id);
		return $sheet_id;
	}

	protected static function availableSheet($sheet_id)
	{
		if(!stickiesSheetModel::available($sheet_id)){
			throw new waRightsException(_w("Not enough rights to work with current board"));
		}
	}

	/**
	 *
	 * @param $sheet_id int
	 * @param $sticky_data array
	 * @return int sticky ID
	 */
	public function create($sheet_id,$sticky_data = array())
	{
		self::availableSheet($sheet_id);
		$default_data = array(
			'sheet_id'=>$sheet_id,
			'creator_contact_id'=>waSystem::getInstance()->getUser()->getId(),
			'create_datetime'=>waDateTime::date('Y-m-d H:i:s'),
			'update_datetime'=>waDateTime::date('Y-m-d H:i:s'),
		);
		$sticky_data = array_merge($sticky_data,$default_data);
		$sticky_data['id'] = $this->insert($sticky_data);
		$sheet = new stickiesSheetModel();
		$sheet->refresh($sheet_id,$this->countByField('sheet_id',$sheet_id));
		return $sticky_data;
	}

	public function modify($id,$options = array())
	{
		$sheet_id = $this->available($id);
		$default_data = array(
			'update_datetime'=>waDateTime::date('Y-m-d H:i:s'),
		);
		$update_count = false;
		if(isset($options['sheet_id'])&&($options['sheet_id']!=$sheet_id)){
			self::availableSheet($options['sheet_id']);
			$update_count = true;
		}

		$res = $this->updateByField(array('id'=>$id,'sheet_id'=>$sheet_id),array_merge($options,$default_data),null,true);
		if($update_count){
			$sheet = new stickiesSheetModel();
			$sheet->refresh($sheet_id,$this->countByField('sheet_id',$sheet_id));
			$sheet->refresh($options['sheet_id'],$this->countByField('sheet_id',$options['sheet_id']));
		}
		if($res && ($res->affectedRows()>=0)){
			return true;
		}else{
			return false;
		}
	}

	public function delete($id)
	{
		$sheet_id = $this->available($id);
		$res = $this->deleteByField(array('id'=>$id,'sheet_id'=>$sheet_id));
		$sheet = new stickiesSheetModel();
		$sheet->refresh($sheet_id,$this->countByField('sheet_id',$sheet_id));
		return $res;
	}
}
