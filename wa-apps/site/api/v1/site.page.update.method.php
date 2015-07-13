<?php

class sitePageUpdateMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $id = $this->get('id', true);
        $page_model = new sitePageModel();
        $page = $page_model->getById($id);

        if ($page) {
            $data = waRequest::post();
            $keys = array('name', 'title', 'content', 'status');
            $update = array();
            foreach ($keys as $k) {
                if (isset($data[$k])) {
                    $update[$k] = $data[$k];
                }
            }
            $r = true;
            if ($update || !empty($data['params'])) {
                if ($update) {
                    $r = $page_model->update($id, $update);
                }
                if (!empty($data['params'])) {
                    $page_model->setParams($id, $data['params']);
                }
            }

            if ($r) {
                $method = new sitePageGetInfoMethod();
                $this->response = $method->getResponse(true);
            } else {
                throw new waAPIException('server_error', 500);
            }

        } else {
            throw new waAPIException('invalid_param', 'Page not found', 404);
        }
    }
}