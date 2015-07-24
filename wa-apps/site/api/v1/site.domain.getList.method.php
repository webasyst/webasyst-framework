<?php

class siteDomainGetListMethod extends waAPIMethod
{
    public function execute()
    {
        $domain_model = new siteDomainModel();
        $this->response['domains'] = $domain_model->getAll();
    }
}