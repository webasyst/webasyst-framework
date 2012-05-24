<?php 

class siteSnippetsSaveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::get('id');
        $info = waRequest::post('info');

        if (!preg_match("/^[a-z0-9_]+$/i", $info['id'])) {
            $this->errors = array(
                _w('Only latin characters, numbers and underscore symbol are allowed.'),
                'input[name="info[id]"]'
            );
            return;
        }

        $model = new siteSnippetModel();
        if ($id) {
            $this->response = $model->updateById($id, $info);
        } else {
            if ($model->add($info)) {
                $this->response = $info;
            }
        }
        if ($this->getConfig()->getOption('cache_time')) {
            waSystem::getInstance()->getView()->clearAllCache();
        }
    }
}