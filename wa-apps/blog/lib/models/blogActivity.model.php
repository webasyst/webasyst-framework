<?php 

class blogActivityModel extends waModel
{
	protected $table = 'blog_activity';
	
	const TYPE_CATEGORY = 1;
	const TYPE_POST = 2;
	const TYPE_BLOG = 3;
	
	public function addActivity($contact_id, $id, $type, $datetime = null)
	{
		return $this->insert(array(
			'contact_id' => $contact_id,
			'id' => intval($id),
			'type' => intval($type),
			'datetime' => $datetime ? $datetime : date("Y-m-d H:i:s"),
		), 1); // on duplicate key update
	}
	
	public function getLastActivity($contact_id, $type)
	{
		return $this->query("SELECT id, datetime FROM `{$this->table}` 
				WHERE contact_id = i:contact_id AND type = i:type", 
			array(
				'contact_id' => $contact_id,
				'type' => $type,
			))->fetchAll('id');
	}
}