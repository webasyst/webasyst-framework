<?php

class sitePageAddMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $data = waRequest::post();
        $domain_id = $this->post('domain_id', true);

        $page_model = new sitePageModel();
        $page_id = $page_model->add($data);

        if ($page_id && !empty($data['params'])) {
            $page_model->setParams($page_id, $data['params']);
        }

        if ($page_id) {
            $_GET['id'] = $page_id;
            $method = new sitePageGetInfoMethod();
            $this->response = $method->getResponse(true);
        } else {
            throw new waAPIException('server_error', 500);
        }

    }
}