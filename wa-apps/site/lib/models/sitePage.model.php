<?php 

class sitePageModel extends waPageModel
{
    protected $app_id = 'site';
	protected $table = 'site_page';


	public function getByUrl($domain_id, $url, $exclude = array())
	{
        $sql = "SELECT * FROM ".$this->table."
                WHERE domain_id = i:domain_id AND url = s:url";
        if ($exclude && is_array($exclude)) {
            $sql .= " AND id NOT IN (i:exclude)";
        }
        return $this->query($sql, array('domain_id' => $domain_id,
                                        'url' => $url, 'exclude' => $exclude))->fetch();
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