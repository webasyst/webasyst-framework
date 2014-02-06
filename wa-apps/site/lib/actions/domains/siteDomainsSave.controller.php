<?php 

class siteDomainsSaveController extends waJsonController
{
    public function execute()
    {
        $name = mb_strtolower(rtrim(waRequest::post('name'), '/'));
        $domain_model = new siteDomainModel();
        $data = array();
        if (!preg_match('!^[a-z0-9/\._-]+$!i', $name)) {
            $data['title'] = $name;
            $idna = new waIdna();
            $name = $idna->encode($name);
        }
        $data['name'] = $name;

        if ($domain_model->getByName($name)) {
            $this->errors = sprintf(_w("Website with a domain name %s is already registered in this Webasyst installation. Delete %s website (Site app > Settings > %s) to be able to use it's domain name for another website."), $name, $name, $name);
            return;
        }

        $this->response['id'] = $domain_model->insert($data);
        $this->log('site_add');
        // add default routing
        $path = $this->getConfig()->getPath('config', 'routing');
        if (file_exists($path)) { 
            $routes = include($path);
        } else {
            $routes = array();
        }
        if (!isset($routes[$name])) {
            $routes[$name]['site'] = array(
                'url' => '*',
                'app' => 'site'
            );
            waUtils::varExportToFile($routes, $path);
        }
    }
}
