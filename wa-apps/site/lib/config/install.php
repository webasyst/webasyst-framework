<?php

$model = new waAppSettingsModel();
$model->set($this->application, 'install_after_trigger', 1);

$domain = $this->getDomain();
if ($domain) {
    $domain_model = new siteDomainModel();
    if (!$domain_model->getByName($domain)) {
        $domain_model->insert(array('name' => $domain));
    }
}

