<?php

class siteDomainsSaveController extends waJsonController
{
    public function execute()
    {
        $name = siteHelper::validateDomainUrl(waRequest::post('name', '', 'string'));
        if (!$name) {
            $this->errors = sprintf(_w("Incorrect domain URL: %s"), waRequest::post('name', '', 'string'));
            return;
        }

        $name = trim($name);
        $original_name = $name; // as was input before any idn encoding


        $domain_model = new siteDomainModel();
        $data = array();
        if (!preg_match('!^[a-z0-9/\._-]+$!i', $name)) {
            $data['title'] = $name;
            $idna = new waIdna();
            $name = $idna->encode($name);
        }
        $data['name'] = $name;

        if ($domain_model->getByName($name)) {
            $error_txt = _w("Website with a domain name %s is already registered in this Webasyst installation. Delete %s website (Site app > Settings > %s) to be able to use it's domain name for another website.");
            $this->errors = sprintf($error_txt, $original_name, $original_name, $original_name);
            return;
        }

        $this->response['id'] = $domain_model->insert($data);
        $this->logAction('site_add', $name);
        // add default routing
        $path = $this->getConfig()->getPath('config', 'routing');
        if (file_exists($path)) {
            $routes = include($path);
        } else {
            $routes = array();
        }

        if (waRequest::post('alias')) {
            $alias_domain = waRequest::post('domain');
            $routes[$name] = $alias_domain;
            waUtils::varExportToFile($routes, $path);
        } else {
            if (!isset($routes[$name])) {
                $routes[$name]['site'] = array(
                    'url' => '*',
                    'app' => 'site',
                    'locale' => wa()->getLocale()
                );
                waUtils::varExportToFile($routes, $path);
            }
        }

        $event_params = $domain_model->getById($this->response['id']) + array(
            'just_created' => true,
            'routes' => $routes[$name],
            'config' => array(),
        );

        /**
         * @event domain_save
         * @return void
         */
        wa('site')->event('domain_save', $event_params);
    }
}
