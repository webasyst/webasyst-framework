<?php 

$domain_model = new siteDomainModel();
$domain = $this->getDomain();
if ($d = $domain_model->getByName($domain)) {
    $domain_id = $d['id'];
} else {
    $domain_id = $domain_model->insert(array('name' => $domain));
}
