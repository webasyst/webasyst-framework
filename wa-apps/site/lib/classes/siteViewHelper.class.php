<?php 

class siteViewHelper extends waAppViewHelper
{
    public function pages($parent_id = 0, $with_params = true)
    {
        if (is_bool($parent_id)) {
            $with_params = $parent_id;
            $parent_id = 0;
        }
        try {
            $domain_model = new siteDomainModel();
            $domain = $domain_model->getByName(waSystem::getInstance()->getRouting()->getDomain(null, true));

            $page_model = new sitePageModel();
            $exclude_ids = waRequest::param('_exclude');
            $sql = "SELECT id, parent_id, name, title, full_url, url, create_datetime, update_datetime FROM ".$page_model->getTableName().'
                    WHERE domain_id = i:domain_id AND route = s:route AND status = 1'.
                    ($exclude_ids ? " AND id NOT IN (:ids)" : '').
                    ' ORDER BY sort';
            $pages = $page_model->query($sql, array(
                'domain_id' => $domain['id'],
                'ids' => $exclude_ids, 'route' => wa()->getRouting()->getRoute('url')))->fetchAll('id');

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
                $page['url'] = $url.$page['full_url'];
                if (!isset($page['title']) || !$page['title']) {
                    $page['title'] = $page['name'];
                }
                foreach ($page as $k => $v) {
                    if ($k != 'content') {
                        $page[$k] = htmlspecialchars($v);
                    }
                }
            }
            unset($page);
            // make tree
            foreach ($pages as $page_id => $page) {
                if ($page['parent_id'] && isset($pages[$page['parent_id']])) {
                    $pages[$page['parent_id']]['childs'][] = &$pages[$page_id];
                }
            }
            foreach ($pages as $page_id => $page) {
                if ($page['parent_id']) {
                    unset($pages[$page_id]);
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