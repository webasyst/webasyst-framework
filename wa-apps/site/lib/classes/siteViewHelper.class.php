<?php 

class siteViewHelper
{
    /**
     * @var waSystem
     */
    protected $wa;
    
    public function __construct($system)
    {
        $this->wa = $system;
    }
    
    public function pages($with_params = true)
    {
        try {
            $domain_model = new siteDomainModel();
            $domain = $domain_model->getByName(waSystem::getInstance()->getRouting()->getDomain(null, true));

            $ids = waRequest::param('_pages');
            if ($ids) {
                $page_model = new sitePageModel();
                $sql = "SELECT id, name, title, url, create_datetime, update_datetime FROM ".$page_model->getTableName().' 
                		WHERE domain_id = i:domain_id AND status = 1 AND id IN (i:ids)
                		ORDER BY sort';
                $pages = $page_model->query($sql, array('domain_id' => $domain['id'], 'ids' => $ids))->fetchAll('id');
            } else {
                $pages = array();
            }
            
            if ($with_params) {
                $page_params_model = new sitePageParamsModel();
                $data = $page_params_model->getByField('page_id', array_keys($pages), true);
                foreach ($data as $row) {
                    $pages[$row['page_id']][$row['name']] = $row['value'];
                }
            }
            // get current rool url
            $url = $this->wa->getAppUrl(null, true);
            foreach ($pages as &$page) {
                $page['url'] = $url.$page['url'];
                if (!isset($page['title']) || !$page['title']) {
                    $page['title'] = $page['name'];
                }
                foreach ($page as $k => $v) {
                    if ($k != 'content') {
                        $page[$k] = htmlspecialchars($v);
                    }
                }
            }
            return $pages;
        } catch (Exception $e) {
            return array();
        }
    }
    
    public function page($id)
    {
        $page_model = new sitePageModel();
        $page = $page_model->getById($id);
        $page['content'] = $this->wa->getView()->fetch('string:'.$page['content']);
        
		$page_params_model = new sitePageParamsModel();
		$page += $page_params_model->getById($id);

		return $page;
    }
}