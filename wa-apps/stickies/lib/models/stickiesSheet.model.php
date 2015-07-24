<?php
/**
 *
 * @author Webasyst
 * @package Stickies
 *
 */
class stickiesSheetModel extends stickiesModel
{
	protected $table = 'stickies_sheet';

	public function refresh($id,$qty)
	{
		$sql = "UPDATE `{$this->table}` SET `qty`= i:qty WHERE `id`=i:id";
		$this->query($sql,array('qty'=>$qty,'id'=>$id));
	}

	public static function available($id)
	{
		$id = max(0,intval($id));
		return $id && wa()->getUser()->getRights(wa()->getApp(),"sheet.{$id}");
	}

	public function get($ignore_rights = false,$fields = null)
	{
		$sheets = $this->getFieldsByField(null,$fields,array('sort'=>'asc'));

		if(!$ignore_rights){
			$system = waSystem::getInstance();
			$app_id = $system->getApp();
			$user = $system->getUser();
			if (!$user->isAdmin($app_id)) {
				$available_sheets = $user->getRights($app_id, 'sheet.%', true);
				foreach ($sheets as $key=>$sheet) {
					$id = $sheet['id'];
					if (!isset($available_sheets[$id])) {
						unset($sheets[$key]);
					}
				}
			}
		}
		return $sheets;
	}

	public function create($name,$background_id = null, $checkRights = true)
	{
		$app_id = wa()->getApp();
		if($checkRights && !wa()->getUser()->getRights($app_id,'add_sheet')){
			throw new waRightsException(_w('Not enough rights to add new board'));
		}
		$sheet = $this->select('MAX(sort) as max_sort')->fetch();
		$data = array(
			'name'=> $name,
			'sort'=> $sheet['max_sort'] + 1,
			'background_id'=>$background_id,
			'creator_contact_id'=>wa()->getUser()->getId(),
			'create_datetime'=>waDateTime::date('Y-m-d H:i:s'),
			'qty' => 0
		);
		if($id = $this->insert($data)){
			wa()->getUser()->setRight($app_id,sprintf('sheet.%d',$id),true);
		}
		return $id;
	}

	public function modify($id,$name,$options = array())
	{
		$data = array_merge(array('name'=>$name),$options);
		return $this->updateById($id,$data);
	}

	public function move($id, $after_id)
	{
		try{
			$sheet = $this->getById($id);
			if (!$sheet) return array('error' => _w("Board not found"));

			if ($after_id != 0) {
				$after_sheet = $this->getById($after_id);
				if (!$after_sheet) return array('error' => _w("Board not found"));
				$sort = $after_sheet['sort'] + 1; // insert after sticky ()
			}
			// move to first
			else{
				$sort = 1;
			}
			if ( $sort > $sheet['sort'] ) {
				$this->exec("UPDATE {$this->table} SET sort = sort - 1 WHERE sort > i:sort_old AND sort <= i:sort",
					array('sort'=>$sort, 'sort_old'=>$sheet['sort']));
			}
			else if ($sort < $sheet['sort']) {
				$this->exec("UPDATE {$this->table} SET sort = sort + 1 WHERE sort >= i:sort AND sort < i:sort_old",
					array('sort' => $sort, 'sort_old' => $sheet['sort']));
			}
			$this->updateById($id, array('sort' => (int)$sort));
		}
		catch(waDbException $e) {
			return array('error' => $e->getMessage());
		}
		return array();
	}

	public function deleteById($value)
	{
		$res = false;
		if($this->available($value)){
			$sticky_model = new stickiesStickyModel();
			$sticky_model->deleteByField('sheet_id',$value);
			$res = parent::deleteById($value);
		}
		return $res;
	}
}
