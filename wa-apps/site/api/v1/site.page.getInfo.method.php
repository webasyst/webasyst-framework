<?php

class sitePageGetInfoMethod extends waAPIMethod
{
    public function execute()
    {
        $id = $this->get('id', true);

        $page_model = new sitePageModel();
        $page = $page_model->getById($id);

        if ($page) {
            $page['params'] = $page_model->getParams($id);
            $this->response = $page;
        } else {
            throw new waAPIException('invalid_request', 'Page not found', 404);
        }
    }
}