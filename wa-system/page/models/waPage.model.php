<?php

class waPageModel extends waModel
{
    protected $app_id;

    public function get($id)
    {
        $page = $this->getById($id);

        if (!$page['status']) {
            $app_settings_model = new waAppSettingsModel();
            $hash = $app_settings_model->get($this->app_id, 'preview_hash');
            if (!$hash || md5($hash) != waRequest::get('preview')) {
                return array();
            }
        }
        if ($page) {
            $params_model = $this->getParamsModel();
            if ($params = $params_model->getById($id)) {
                $page += $params;
            }
            if (!$page['title']) {
                $page['title'] = $page['name'];
            }
        }
        return $page;
    }


    public function add($data)
    {
        if (!isset($data['create_contact_id'])) {
            $data['create_contact_id'] = wa()->getUser()->getId();
        }
        if (!isset($data['create_datetime'])) {
            $data['create_datetime'] = date("Y-m-d H:i:s");
        }
        $data['update_datetime'] = date("Y-m-d H:i:s");
        $data['sort'] = (int)$this->select("MAX(sort)")->fetchField() + 1;
        return $this->insert($data);
    }


    public function update($id, $data)
    {
        $data['update_datetime'] = date("Y-m-d H:i:s");
        return $this->updateById($id, $data);
    }

    public function delete($id)
    {
        $page = $this->getById($id);
        if ($page) {
            $params_model = $this->getParamsModel();
            $params_model->deleteByField('page_id', $id);

            if ($this->deleteById($id)) {
                // update sort
                $this->exec("UPDATE ".$this->table." SET sort = sort - 1 WHERE sort > i:sort", array('sort' => $page['sort']));
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function move($id, $sort)
    {
        if (!$id) {
            return false;
        }
        $sort = (int)$sort;
        // get page
        $page = $this->getById($id);
        // get real sort
        $sql = "SELECT sort FROM ".$this->table." ORDER BY sort LIMIT ".($sort ? $sort - 1 : 0).', 1';
        $sort = $this->query($sql)->fetchField('sort');

        if ($page) {
            if ($page['sort'] < $sort) {
                $sql = "UPDATE ".$this->table." SET sort = sort - 1
	            		WHERE sort > ".$page['sort']." AND sort <= ".$sort;
            } elseif ($page['sort'] > $sort) {
                $sql = "UPDATE ".$this->table." SET sort = sort + 1
	            		WHERE sort >= ".$sort." AND sort < ".$page['sort'];
            }
            $this->exec($sql);
            $this->updateById($id, array('sort' => $sort));
        }
        return false;
    }

    /**
     * @return waPageParamsModel
     */
    public function getParamsModel()
    {
        $class = get_class($this);
        $class = str_replace('Model', 'ParamsModel', $class);
        return new $class();
    }

    public function getParams($id)
    {
        return $this->getParamsModel()->getById($id);
    }

    public function setParams($id, $params)
    {
        return $this->getParamsModel()->save($id, $params);
    }
}
