<?php

class siteVariablesSaveController extends waJsonController {
    public function execute() {
        $id = waRequest::get('id');
        $info = waRequest::post('info');

        if (!preg_match('/^[a-z0-9\._-]+$/i', $info['id'])) {
            $this->errors = [
                _w('Only Latin letters, numbers, hyphens and underscore characters are allowed.'),
                'input[name="info[id]"]',
            ];
            return;
        }

        $model = new siteVariableModel();

        if ($id) {
            try {
                $model->updateById($id, $info);
                $this->logAction('variable_edit');
                if ($id != $info['id']) {
                    $info['old_id'] = $id;
                }
                $this->response($info);
            } catch (waDbException $wde) {
                if ($wde->getCode() === 1406) {
                    $this->errors = [
                        _w('The variable is too large. Reduce it or create several variables instead of one.'),
                        'input[name="info[content]"]',
                    ];
                } else {
                    throw $wde;
                }
            } catch (Exception $e) {
                if ($model->getById($info['id'])) {
                    $this->errors = [
                        sprintf(_w('A variable with ID “%s” already exists.'), $info['id']),
                        'input[name="info[id]"]',
                    ];
                } else {
                    throw $e;
                }
            }
        } else {
            try {
                $model->add($info);
                $this->logAction('variable_add');
                $this->response($info);
            } catch (waDbException $wde) {
                if ($wde->getCode() === 1406) {
                    $this->errors = [
                        _w('The variable is too large. Reduce it or create several variables instead of one.'),
                        'input[name="info[content]"]',
                    ];
                } else {
                    throw $wde;
                }
            } catch (Exception $e) {
                if ($model->getById($info['id'])) {
                    $this->errors = [
                        sprintf(_w('A variable with ID “%s” already exists.'), $info['id']),
                        'input[name="info[id]"]',
                    ];
                } else {
                    throw $e;
                }
            }
        }
        if ($this->getConfig()->getOption('cache_time')) {
            waSystem::getInstance()->getView()->clearAllCache();
        }
    }

    public function response($info) {
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
