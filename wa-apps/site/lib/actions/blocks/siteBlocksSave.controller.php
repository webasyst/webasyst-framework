<?php

class siteBlocksSaveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::get('id');
        $info = waRequest::post('info');

        if (!preg_match('/^[a-z0-9\._-]+$/i', $info['id'])) {
            $this->errors = array(
                _w('Only Latin letters, numbers, and the underscore character are allowed.'),
                'input[name="info[id]"]'
            );
            return;
        }

        $model = new siteBlockModel();

        try {
            if ($id) {
                $model->updateById($id, $info);
                $this->logAction('block_edit');
                if ($id != $info['id']) {
                    $info['old_id'] = $id;
                }
                $this->response($info);
            } else {
                $model->add($info);
                $this->logAction('block_add');
                $this->response($info);
            }
        } catch (waDbException $wde) {
            switch ($wde->getCode()) {
                case 1062:
                    $this->errors = [
                        sprintf(_w('Block with id “%s” already exists.'), $info['id']),
                        'input[name="info[id]"]',
                        1
                    ];
                    break;
                case 1406:
                    $this->errors = [
                        _w('The block is too large. Reduce it or create several blocks instead of one.'),
                        'input[name="info[content]"]'
                    ];
                    break;
                default:
                    throw $wde;
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
