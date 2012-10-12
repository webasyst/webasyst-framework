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
        return $this->query($sql, array('domain_id' => $domain_id, 'route' => $route, 'url' => $url))->fetch();
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

}