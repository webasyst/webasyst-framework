<?php

class siteSavePage extends waPageActions
{
    public function savePage(?int $id, array $data)
    {
        $data['url'] = trim(ifset($data, 'url', ''), '/');
        if (strlen($data['url']) > 0) {
            $data['url'] .= '/';
        }

        if (empty($data['name'])) {
            $data['name'] = '('._ws('no-title').')';
        }
        $data['status'] = isset($data['status']) ? 1 : 0;

        $page_model = $this->getPageModel();

        if ($id) {
            $is_new = false;
            $old = $page_model->getById($id);
            if (!empty($old['parent_id'])) {
                $parent = $this->getPage($old['parent_id']);
                $parent_full_url = $parent['full_url'] ? rtrim($parent['full_url'], '/') . '/' : '';
                $data['full_url'] = $parent_full_url . $data['url'];
            } else {
                $data['full_url'] = $data['url'];
            }
            // save to database
            if (!$page_model->update($id, $data)) {
                return [
                    'error' => _ws('Error saving web page')
                ];
            }
            $this->logAction('page_edit', $id);
            $childs = $page_model->getChilds($id);
            if ($childs) {
                $page_model->updateFullUrl($childs, $data['full_url'], $old['full_url']);
            }
        } else {
            if (!empty($data['parent_id'])) {
                $parent = $this->getPage($data['parent_id']);
                $parent_full_url = $parent['full_url'] ? rtrim($parent['full_url'], '/') . '/' : '';
                $data['full_url'] = $parent_full_url . $data['url'];
                $data['domain'] = $parent['domain'];
                $data['route'] = $parent['route'];
                $this->beforeSave($data, $parent);
            } else {
                $data['full_url'] = $data['url'];
                $this->beforeSave($data);
            }
            $is_new = true;
            $id = $page_model->add($data);
            if ($id) {
                $data['id'] = $id;
                $this->logAction('page_add', $id);
            } else {
                return [
                    'error' => _ws('Error saving web page')
                ];
            }
        }

        // save params
        $this->savePageParams($data, $id);

        /**
         * New page created or existing page modified.
         *
         * @event page_save
         * @param array $params
         * @param array[array] $params['page'] page data after save
         * @param array[array] $params['old'] page data before save (null if page is new)
         * @return void
         */
        $event_params = [
            'page' => $page_model->getById($id),
            'old' => $is_new ? null : $old,
        ];
        wa()->event('page_save', $event_params);
        $data = $event_params['page'];

        // prepare response
        return [
            'id' => $id,
            'name' => htmlspecialchars($data['name']),
            'add' => $is_new ? 1 : 0,
            'url' => $data['url'],
            'full_url' => $data['full_url'],
            'old_full_url' => isset($old) ? $old['full_url'] : '',
            'status' => $data['status']
        ];
    }

    protected function savePageParams($data, $id)
    {
        $params = ifempty($data, 'params', []);
        $other_params = ifempty($data, 'other_params', '');
        if ($other_params) {
            $other_params = explode("\n", $other_params);
            foreach ($other_params as $param) {
                $param = explode("=", trim($param), 2);
                if (count($param) == 2) {
                    $params[$param[0]] = $param[1];
                }
            }
        }
        $og = $data['og'];
        foreach ($og as $k => $v) {
            if ($v) {
                $params['og_'.$k] = $v;
            }
        }
        $this->getPageModel()->setParams($data['id'] ?? $id, $params);
    }
}
