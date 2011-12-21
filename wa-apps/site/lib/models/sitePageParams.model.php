<?php 

class sitePageParamsModel extends waModel
{
	protected $table = 'site_page_params';

	public function getById($id)
	{
	    $sql = "SELECT name, value FROM ".$this->table." WHERE page_id = i:id";
	    return $this->query($sql, array('id' => $id))->fetchAll('name', true);
	}
}