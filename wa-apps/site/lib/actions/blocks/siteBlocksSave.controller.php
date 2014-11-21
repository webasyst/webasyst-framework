<?php

class siteBlocksSaveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::get('id');
        $info = waRequest::post('info');

        if (!preg_match("/^[a-z0-9\._]+$/i", $info['id'])) {
            $this->errors = array(
                _w('Only latin characters, numbers and underscore symbol are allowed.'),
                'input[name="info[id]"]'
            );
            return;
        }

        $model = new siteBlockModel();

        if ($id) {
            try {
                $model->updateById($id, $info);
                $this->logAction('block_edit');
                if ($id != $info['id']) {
                    $info['old_id'] = $id;
                }
                $this->response($info);
            } catch (Exception $e) {
                if ($model->getById($info['id'])) {
                    $this->errors = array(
                        _w('Block with id "%s" already exists', null, null, $info['id']) // _w('Block with id "%s" already exists')
                    );
                } else {
                    throw $e;
                }
            }
        } else {
            try {
                $model->add($info);
                $this->logAction('block_add');
                $this->response($info);
            } catch (Exception $e) {
                if ($model->getById($info['id'])) {
                    $this->errors = array(
                        _w('Block with id "%s" already exists', null, null, $info['id']) // _w('Block with id "%s" already exists')
                    );
                } else {
                    throw $e;
                }
            }
        }
        if ($this->getConfig()->getOption('cache_time')) {
            waSystem::getInstance()->getView()->clearAllCache();
        }
    }

    public function response($info)
    {
        if (($pos = strpos($info['id'], '.')) !== false) {
            $app_id = substr($info['id'], 0, $pos);
            $apps = wa()->getApps();
            if (isset($apps[$app_id])) {
                $info['app_icon'] = $apps[$app_id]['icon'];
            }
        }
        $info['id'] = htmlspecialchars($info['id']);
        $info['description'] = htmlspecialchars($info['description']);
        $this->response = $info;
    }
}