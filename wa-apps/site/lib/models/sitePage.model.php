<?php

class sitePageModel extends waPageModel
{
    protected $app_id = 'site';
    protected $table = 'site_page';
    protected $domain_field = 'domain_id';


    public function getByUrl($domain_id, $route, $url)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE domain_id = i:domain_id AND route = s:route AND full_url = s:url";
        return $this->query($sql, array('domain_id' => $domain_id, 'route' => $route, 'url' => $url))->fetchAssoc();
    }

    public function updateDomain($old_domain, $new_domain)
    {
        // nothing
    }

    public function updateRoute($domain, $old_route, $new_route)
    {
        $domain_model = new siteDomainModel();
        $domain = $domain_model->getByName($domain);
        if ($domain) {
            return $this->updateByField(array(
                'domain_id' => $domain['id'], 'route' => $old_route), array('route' => $new_route));
        }
    }

    public function getByDomain($domain_id, $route = null, $content = false)
    {
        $sql = "SELECT id, parent_id, name, title, full_url, url, route, create_datetime, update_datetime, status".
            ($content ? ', content' : '')." FROM ".$this->table.'
            WHERE domain_id = i:0 '.($route !== null ? ' AND route = s:1' : '').'
            ORDER BY sort';
        return $this->query($sql, $domain_id, $route)->fetchAll('id');
    }
}