<?php

class siteDomainGetListMethod extends waAPIMethod
{
    public function execute()
    {
        $domain_model = new siteDomainModel();
        $domains = $domain_model->getAll();

        if (!wa()->getUser()->isAdmin('site')) {
            $allowed_domain_ids = wa()->getUser()->getRights('site', 'domain.%');
            foreach ($domains as $key => $domain) {
                if (!isset($allowed_domain_ids[$domain['id']])) {
                    unset($domains[$key]);
                }
            }
        }

        $this->response['domains'] = array_values($domains);
    }
}
