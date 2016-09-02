<?php

class waPageModel extends waModel
{
    protected $app_id;
    protected $domain_field = 'domain';

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

    public function updateDomain($old_domain, $new_domain)
    {
        return $this->updateByField(array('domain' => $old_domain), array('domain' => $new_domain));
    }

    public function updateRoute($domain, $old_route, $new_route)
    {
        return $this->updateByField(array('domain' => $domain, 'route' => $old_route), array('route' => $new_route));
    }

    public function updateFullUrl($ids, $new_url, $old_url)
    {
        if ($new_url && substr($new_url, -1, 1) != '/') {
            $new_url .= '/';
        }
        if ($old_url && substr($old_url, -1, 1) != '/') {
            $old_url .= '/';
        }
        $sql = "UPDATE ".$this->table."
        SET full_url = CONCAT(s:url, SUBSTR(full_url, ".(strlen($old_url) + 1) ."))
        WHERE id IN (i:ids)";
        return $this->exec($sql, array('ids' => $ids, 'url' => $new_url));
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
        // SET sort
        $sql = "SELECT MAX(sort) FROM ".$this->table."
                WHERE ".$this->domain_field." = s:".$this->domain_field." AND route = s:route AND parent_id ".
                (!isset($data['parent_id']) || $data['parent_id'] === null ? "IS NULL" : " = i:parent_id");
        $data['sort'] = (int)$this->query($sql, $data)->fetchField() + 1;
        $r = $this->insert($data);
        $this->clearCache();
        return $r;
    }


    public function update($id, $data)
    {
        $data['update_datetime'] = date("Y-m-d H:i:s");
        $r = $this->updateById($id, $data);
        $this->clearCache();
        return $r;
    }

    public function delete($id)
    {
        $params_model = $this->getParamsModel();
        if (is_array($id)) {
            $params_model->deleteByField('page_id', $id);
            return $this->deleteById($id);
        } else {
            $page = $this->getById($id);
            if ($page) {
                $params_model->deleteByField('page_id', $id);

                if ($this->deleteById($id)) {
                    // update sort
                    $this->updateSortOnDelete($page);
                    $this->clearCache();
                    return true;
                } else {
                    return false;
                }
            }
        }
        return false;
    }

    public function updateSortOnDelete($page)
    {
        $this->exec("UPDATE ".$this->table." SET sort = sort - 1
                    WHERE sort > i:sort AND
                          parent_id ".($page['parent_id'] === null ? 'IS NULL' : '= i:parent_id')." AND
                          ".$this->domain_field." = s:".$this->domain_field." AND
                          route = s:route", $page);
    }

    public function move($id, $parent_id, $before_id = null)
    {
        if (!$id) {
            return false;
        }
        $data = array();
        // get page
        $page = $this->getById($id);
        if (!$page) {
            return false;
        }
        // get parent
        if (is_numeric($parent_id)) {
            $parent = $this->getById($parent_id);
            if ($parent['full_url'] && substr($parent['full_url'], -1) != '/') {
                $parent['full_url'] .= '/';
            }
            $domain = $parent[$this->domain_field];
            $route = $parent['route'];
        } else {
            $domain = $parent_id[$this->domain_field];
            $route = $parent_id['route'];
            $parent_id = null;
        }
        if ($page[$this->domain_field] != $domain) {
            $data[$this->domain_field] = $domain;
        }
        if ($page['route'] != $domain) {
            $data['route'] = $route;
        }
        if ($page['parent_id'] != $parent_id) {
            $data['parent_id'] = $parent_id;
            if ($parent_id) {
                $data['full_url'] = $parent['full_url'].$page['url'];
            } else {
                $data['full_url'] = $page['url'];
            }
        }
        if ($before_id && $before = $this->getById($before_id)) {
            $data['sort'] = $before['sort'];
            $sql = "UPDATE ".$this->table." SET sort = sort + 1
                    WHERE parent_id ".($before['parent_id'] === null ? 'IS NULL' : '= i:parent_id')." AND
                        ".$this->domain_field." = s:".$this->domain_field." AND route = s:route AND sort >= i:sort";
            $this->exec($sql, $before);
        } else {
            $sql = "SELECT MAX(sort) FROM ".$this->table."
                    WHERE parent_id ".($parent_id === null ? 'IS NULL' : '= i:parent_id')." AND
                    ".$this->domain_field." = s:".$this->domain_field." AND route = s:route";
            $data['sort'] = (int)$this->query($sql, array('parent_id' => $parent_id, $this->domain_field => $domain, 'route' => $route))->fetchField() + 1;
        }
        $this->updateSortOnDelete($page);
        if ($this->updateById($id, $data)) {
            if (isset($data[$this->domain_field]) || isset($data['route']) || isset($data['parent_id'])) {
                $childs = $this->getChilds($id);
                if ($childs) {
                    if (isset($data['parent_id'])) {
                        // set new full url for childs
                        $this->updateFullUrl($childs, $data['full_url'], $page['full_url']);
                    }
                    // update domain and route for childs
                    $update = array();
                    if (isset($data[$this->domain_field])) {
                        $update[$this->domain_field] = $data[$this->domain_field];
                    }
                    if (isset($data['route'])) {
                        $update['route'] = $data['route'];
                    }
                    if ($update) {
                        $this->updateById($childs, $update);
                    }
                }
            }
            $data['id'] = $id;
            if (isset($data['full_url'])) {
                $data['old_full_url'] = $page['full_url'];
                if ($data['old_full_url'] && substr($data['old_full_url'], -1) != '/') {
                    $data['old_full_url'] .= '/';
                }
            }
            $this->clearCache();
            return $data;
        } else {
            return false;
        }
    }

    public function getChilds($id)
    {
        $result = array();
        $ids = array($id);
        $sql = "SELECT id FROM ".$this->table." WHERE parent_id IN (i:ids)";
        while ($ids = $this->query($sql, array('ids' => $ids))->fetchAll(null, true)) {
            $result = array_merge($result, $ids);
        }
        return $result;
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
        $this->clearCache();
        return $this->getParamsModel()->save($id, $params);
    }

    public function getPublishedPages($domain, $route)
    {
        $sql = "SELECT id, parent_id, name, title, full_url, url, create_datetime, update_datetime FROM ".$this->table.'
                    WHERE status = 1 AND '.$this->domain_field.' = s:domain AND route = s:route ORDER BY sort';
        return $this->query($sql, array('domain' => $domain, 'route' => $route))->fetchAll('id');
    }

    public function clearCache()
    {
        if ($cache = wa($this->app_id)->getCache()) {
            $cache->deleteGroup('pages');
        }
    }
    
    public function getDomainField()
    {
        return $this->domain_field;
    }
}
