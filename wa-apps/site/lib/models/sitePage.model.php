<?php 

class sitePageModel extends waModel
{
	protected $table = 'site_page';

	public function getByUrl($domain_id, $ids, $url)
	{
		return $this->getByField(array('domain_id' => $domain_id, 'id' => $ids, 'url' => $url));
	}
	
	public function get($id)
	{
	    $page = $this->getById($id);
	     
	    if (!$page['status']) {
	        $app_settings_model = new waAppSettingsModel();
	        $hash = $app_settings_model->get('site', 'preview_hash');
	        if (!$hash || md5($hash) != waRequest::get('preview')) {	        
	            return array();
	        }
	    }
	    if ($page) {
	        $params_model = new sitePageParamsModel();
	        if ($params = $params_model->getById($id)) {
	            $page += $params;
	        }
    	    if (!$page['title']) {
    	        $page['title'] = $page['name'];
    	    }	        
	    }
	    return $page;
	}
	
	public function add($data)
	{
		if (!isset($data['create_contact_id'])) {
			$data['create_contact_id'] = wa()->getUser()->getId();
		}
		if (!isset($data['create_datetime'])) {
			$data['create_datetime'] = date("Y-m-d H:i:s");
		}
		$data['update_datetime'] = date("Y-m-d H:i:s");
		$data['sort'] = (int)$this->select("MAX(sort)")->fetchField() + 1;
		return $this->insert($data);
	}
	
	public function update($id, $data)
	{
	    $data['update_datetime'] = date("Y-m-d H:i:s");
	    return $this->updateById($id, $data);
	}
	
	public function delete($id)
	{
	    $page = $this->getById($id);
	    if ($page) {
    		$params_model = new sitePageParamsModel();
    		$params_model->deleteByField('page_id', $id);
    		
    		if ($this->deleteById($id)) {
    		    // update sort
    		    $this->exec("UPDATE ".$this->table." SET sort = sort - 1 WHERE sort > i:sort", array('sort' => $page['sort']));
    		    return true;
    		} else {
    		    return false;
    		}
	    }
	    return false;
	}
	
	public function move($id, $sort)
	{
	    if (!$id) {
	        return false;
	    }
	    $sort = (int)$sort;
	    // get page
	    $page = $this->getById($id);
	    $domain_id = (int)$page['domain_id'];	    
	    // get real sort
	    $sql = "SELECT sort FROM ".$this->table."
	    		WHERE domain_id = ".$domain_id." ORDER BY sort LIMIT ".($sort ? $sort - 1 : 0).', 1';
	    $sort = $this->query($sql)->fetchField('sort');

	    if ($page) {
	        if ($page['sort'] < $sort) {
	            $sql = "UPDATE ".$this->table." SET sort = sort - 1 
	            		WHERE domain_id = ".$domain_id." AND sort > ".$page['sort']." AND sort <= ".$sort;
	        } elseif ($page['sort'] > $sort) {
	            $sql = "UPDATE ".$this->table." SET sort = sort + 1 
	            		WHERE domain_id = ".$domain_id." AND sort >= ".$sort." AND sort < ".$page['sort'];
	        }
	        $this->exec($sql);
	        $this->updateById($id, array('sort' => $sort));
	    }
	    return false;
	}
}