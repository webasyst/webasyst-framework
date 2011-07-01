<?php

class waContactsCollection
{
    protected $hash;

    protected $order_by;
    protected $group_by;
    protected $where;
    protected $where_fields = array();
    protected $joins;

    protected $title = '';
    protected $count;

    protected $options = array(
        'check_rights' => true
    );

    protected $update_count;


    protected $post_fields;
    protected $prepared;

    protected $alias_index = array();

    protected $models;

    /**
     * Constructor for collection of contacts
     *
     * @param string $hash - search hash
     * @example
     *        All contacts where name contains John
     *     $collection = new contactsCollection('/search/name*=John/');
     *
     *     All contacts in the list with id 100500
     *     $collection = new contactsCollection('/list/100500/');
     *
     *     Contacts with ids from list
     *     $collection = new contactsCollection('/id/1,10,100,500/');
     *     or
     *     $collection = new contactsCollection('/search/id=1,10,100,500/');
     *
     *     All contacts
     *     $collection = new contactsCollection();
     */
    public function __construct($hash = '', $options = array())
    {
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
        $this->setHash($hash);
    }

    protected function setHash($hash)
    {
        if (substr($hash, 0, 1) == '#') {
            $hash = substr($hash, 1);
        }
        $this->hash = trim($hash, '/');
        if (substr($this->hash, 0 ,9) == 'contacts/') {
            $this->hash = substr($this->hash, 9);
        }
        if ($this->hash == 'all') {
            $this->hash = '';
        }
        $this->hash = explode('/', $this->hash);
    }

    /**
     * Returns count of the all contacts in collection
     *
     * @return int
     */
    public function count()
    {
        if ($this->count === null) {
            $sql = $this->getSQL();
            $sql = "SELECT COUNT(".($this->joins ? 'DISTINCT ' : '')."c.id) ".$sql;
            $this->count = (int)$this->getModel()->query($sql)->fetchField();

            if ($this->update_count && $this->count != $this->update_count['count']) {
                $this->update_count['model']->updateCount($this->update_count['id'], $this->count);
            }
        }
        return $this->count;
    }


    public function getTitle()
    {
        if ($this->title === null) {
            $this->prepare();
        }
        return $this->title;
    }

    protected function getFields($fields)
    {
        $contact_model = $this->getModel();
        if ($fields == '*') {
            $fields = $contact_model->getMetadata();
            unset($fields['password']);
            $fields = array_keys($fields);
            $this->post_fields['_internal'] = array('_online_status');
            $this->post_fields['email'] = array('email');
            $this->post_fields['data'] = array();
            return 'c.'.implode(",c.", $fields);
        }

        $required_fields = array('id' => 'c'); // field => table, to be added later in any case

        if (!is_array($fields)) {
            $fields = explode(",", $fields);
        }

        // Add required fields to select and delete fields for getting data after query
        foreach ($fields as $i => $f) {
            if (!$contact_model->fieldExists($f)) {
                if ($f == 'email') {
                    $this->post_fields['email'][] = $f;
                } else if ($f == '_online_status') {
                    $required_fields['last_datetime'] = 'c';
                    $required_fields['login'] = 'c';
                    $this->post_fields['_internal'][] = $f;
                } else if ($f == '_access') {
                    $this->post_fields['_internal'][] = $f;
                } else {
                    $this->post_fields['data'][] = $f;
                }
                unset($fields[$i]);
                continue;
            }

            if (isset($required_fields[$f])) {
                $fields[$i] = ($required_fields[$f] ? $required_fields[$f]."." : '').$f;
                unset($required_fields[$f]);
            }
        }

        foreach ($required_fields as $field => $table) {
            $fields[] = ($table ? $table."." : '').$field;
        }

        return implode(",", $fields);
    }

    /**
     * Get data for contacts in this collection.
     *
     * @return array [contact_id][field] = field value in appropriate field format
     */
    public function getContacts($fields = "id", $offset = 0, $limit = 50)
    {
        $sql = "SELECT ".($this->joins ? 'DISTINCT ' : '').$this->getFields($fields)." ".$this->getSQL();
        $sql .= $this->getGroupBy();
        $sql .= $this->getOrderBy();
        $sql .= " LIMIT ".($offset ? $offset.',' : '').(int)$limit;
        //header("X-SQL: ".str_replace("\n", " ", $sql));
        $data = $this->getModel()->query($sql)->fetchAll('id');
        $ids = array_keys($data);

        //
        // Load fields from other storages
        //
        if ($ids && $this->post_fields) {
            // $fill[table][field] = null
            // needed for all rows to always contain all apropriate keys
            // in case when we're asked to load all fields from that table
            $fill = array_fill_keys(array_keys($this->post_fields), array());
            foreach (waContactFields::getAll('enabled') as $fid => $field) {
                $fill[$field->getStorage(true)][$fid] = false;
            }

            foreach ($this->post_fields as $table => $fields) {
                if ($table == '_internal') {
                    foreach ($fields as $f) {
                        switch($f) {
                            case '_online_status':
                                $t = time() - waUser::getOption('online_timeout', 300);
                                foreach($data as &$v) {
                                    $v['_online_status'] = waUser::getStatusByInfo($v);
                                }
                                unset($v);
                                break;
                            case '_access':
                                $rm = new waContactRightsModel();
                                $accessStatus = $rm->getAccessStatus($ids);
                                foreach($data as $id => &$v) {
                                    if (!isset($accessStatus[$id])) {
                                        $v['_access'] = '';
                                        continue;
                                    }
                                    $v['_access'] = $accessStatus[$id];
                                }
                                unset($v);
                                break;
                            default:
                                throw new waException('Unknown internal field: '.$f);
                        }
                    }
                    continue;
                }

                $model = $this->getModel($table);
                $post_data = $model->getData($ids, $fields);

                foreach ($post_data as $contact_id => $contact_data) {
                    foreach ($contact_data as $field_id => $value) {
                        if (! ( $f = waContactFields::get($field_id))) {
                            continue;
                        }
                        if (!$f->isMulti()) {
                            $post_data[$contact_id][$field_id] = $value[0]['value'];
                        }
                    }
                }
                if ($fields) {
                    $fill[$table] = array_fill_keys($fields, '');
                } else if (!isset($fill[$table])) {
                    $fill[$table] = array();
                }

                foreach ($data as $contact_id => $v) {
                    if (isset($post_data[$contact_id])) {
                        $data[$contact_id] += $post_data[$contact_id];
                    }
                    $data[$contact_id] += $fill[$table];
                }
            }
        }

        return $data;
    }


    protected function prepare($new = false, $auto_title = true)
    {
        if (!$this->prepared || $new) {
            $type = $this->hash[0];
            if ($type) {
                $method = strtolower($type).'Prepare';
                if (method_exists($this, $method)) {
                    $this->$method(isset($this->hash[1]) ? $this->hash[1] : '', $auto_title);
                }
            } elseif ($auto_title) {
                $this->addTitle(_ws('All contacts'));
            }

            if ($this->prepared) {
                return;
            }
            $this->prepared = true;
            if ($this->options['check_rights'] && !wa()->getUser()->getRights('contacts', 'category.all')) {
                // Add user rights
                $group_ids = waSystem::getInstance()->getUser()->getGroups();
                $group_ids[] = 0;
                $group_ids[] = -waSystem::getInstance()->getUser()->getId();
                $this->joins[] = array(
                    'table' => 'wa_contact_categories',
                    'alias' => 'cc',
                );
                $this->joins[] = array(
                    'table' => 'contacts_rights',
                    'alias' => 'r',
                    'on' => 'r.category_id = cc.category_id AND r.group_id IN ('.implode(",", $group_ids).')'
                );
            }
        }
    }

    protected function idPrepare($ids)
    {
        $ids = explode(',', $ids);
        foreach ($ids as $k => $v) {
            $v = (int)$v;
            if ($v) {
                $ids[$k] = $v;
            } else {
                unset($ids[$k]);
            }
        }
        if ($ids) {
            $this->where[] = "c.id IN (".implode(",", $ids).")";
        }
    }

    protected function searchPrepare($query, $auto_title = true)
    {
        if ($auto_title || !isset($this->alias_index['data'])) {
            $this->alias_index['data'] = 0;
        }

        $query = urldecode($query);

        // `&` can be escaped in search request. Need to split by not escaped ones only.
        $escapedBS = 'ESCAPED_BACKSLASH';
        while(FALSE !== strpos($query, $escapedBS)) {
            $escapedBS .= rand(0, 9);
        }
        $escapedAmp = 'ESCAPED_AMPERSAND';
        while(FALSE !== strpos($query, $escapedAmp)) {
            $escapedAmp .= rand(0, 9);
        }
        $query = str_replace('\\&', $escapedAmp, str_replace('\\\\', $escapedBS, $query));
        $query = explode('&', $query);

        $model = $this->getModel();
        $title = array();
        foreach ($query as $part) {
            if (! ( $part = trim($part))) {
                continue;
            }
            $part = str_replace(array($escapedBS, $escapedAmp), array('\\\\', '\\&'), $part);
            $parts = preg_split("/(\\\$=|\^=|\*=|==|!=|>=|<=|=|>|<)/uis", $part, 2, PREG_SPLIT_DELIM_CAPTURE);
            if ($parts) {
                if ($parts[0] == 'email') {
                    if (!isset($this->joins['email'])) {
                        $this->joins['email'] = array(
                            'table' => 'wa_contact_emails',
                            'alias' => 'e'
                        );
                    }
                    $title[] = waContactFields::get($parts[0])->getName().$parts[1].$parts[2];
                    $this->where[] = 'e.email'.$this->getExpression($parts[1], $parts[2]);
                } elseif ($model->fieldExists($parts[0])) {
                    if ($f = waContactFields::get($parts[0])) {
                        $title[] = $f->getName().$parts[1].$parts[2];
                    } else {
                        $title[] = $parts[0].$parts[1].$parts[2];
                    }
                    $this->where[] = 'c.'.$parts[0].$this->getExpression($parts[1], $parts[2]);
                } else {
                    $alias = "d".($this->alias_index['data']++);
                    $field_parts = explode('.', $parts[0]);
                    $f = $field_parts[0];
                    $title[] = waContactFields::get($f)->getName().$parts[1].$parts[2];
                    $ext = isset($field_parts[1]) ? $field_parts[1] : null;
                    $on = $alias.'.contact_id = c.id AND '.$alias.".field = '".$model->escape($f)."'";
                    $on .= ' AND '.$alias.".value ".$this->getExpression($parts[1], $parts[2]);
                    if ($ext !== null) {
                        $on .= " AND ".$alias.".ext = '".$model->escape($ext)."'";
                    }
                    $this->joins[] = array(
                        'table' => 'wa_contact_data',
                        'alias' => $alias,
                        'on' => $on
                    );
                    $this->where_fields[] = $f;
                }
            }
        }

        if ($title) {
            $title = implode(', ', $title);

            // Strip slashes from search title.
            $bs = '\\\\';
            $title = preg_replace("~{$bs}(_|%|&|{$bs})~", '\1', $title);
        }
        if ($auto_title) {
            $this->addTitle($title, ' ');
        }
    }


    public function addTitle($title, $delim = ', ')
    {
        if (!$title) {
            return;
        }
        if ($this->title) {
            $this->title .= $delim;
        }
        $this->title .= $title;
    }

    public function getWhereFields()
    {
        return $this->where_fields;
    }

    protected function categoryPrepare($id)
    {

        $category_model = new waContactCategoryModel();
        $category = $category_model->getById($id);

        if ($category) {
            $this->title = $category['name'];
            $this->update_count = array(
                'model' => $category_model,
                'id' => $id,
                'count' => isset($category['cnt']) ? $category['cnt'] : 0
            );
        }

        $this->joins[] = array(
            'table' => 'wa_contact_categories',
            'alias' => 'ct',
        );
        $this->where[] = "ct.category_id = ".(int)$id;
    }

    protected function usersPrepare($params, $auto_title = true)
    {
        $this->where[] = 'c.is_user = 1';
        if ($auto_title) {
            $this->addTitle(_ws('All users'));
        }
    }

    protected function groupPrepare($id)
    {
        $group_model = new waGroupModel();
        $group = $group_model->getById($id);

        if ($group) {
            $this->title = $group['name'];
            $this->update_count = array(
                'model' => $group_model,
                'id' => $id,
                'count' => isset($group['cnt']) ? $group['cnt'] : 0
            );
        }

        $this->joins[] = array(
            'table' => 'wa_user_groups',
            'alias' => 'cg',
        );
        $this->where[] = "cg.group_id = ".(int)$id;
    }



    protected function getOrderBy()
    {
        if ($this->order_by) {
            return " ORDER BY ".$this->order_by;
        } else {
            return "";
        }
    }

    protected function getGroupBy()
    {
        if ($this->group_by) {
            return " GROUP BY ".$this->group_by;
        } else {
            return "";
        }
    }

    /**
     * Returns contacts model
     *
     * @return waContactModel|waContactDataModel|waContactEmailsModel
     */
    protected function getModel($type = null)
    {
        switch ($type) {
            case 'data':
                if (!isset($this->models[$type])) {
                    $this->models[$type] = new waContactDataModel();
                }
                return $this->models[$type];
            case 'email':
                if (!isset($this->models[$type])) {
                    $this->models[$type] = new waContactEmailsModel();
                }
                return $this->models[$type];
            default:
                $type = 'default';
                if (!isset($this->models[$type])) {
                    $this->models[$type] = new waContactModel();
                }
                return $this->models[$type];
        }
    }

    protected function getExpression($op, $value)
    {
        $model = $this->getModel();
        switch ($op) {
            case '>':
            case '>=':
            case '<':
            case '<=':
            case '!=':
                return " ".$op." '".$model->escape($value)."'";
            case "^=":
                return " LIKE '".$model->escape($value)."%'";
            case "$=":
                return " LIKE '%".$model->escape($value)."'";
            case "*=":
                return " LIKE '%".$model->escape($value)."%'";
            case "==":
            case "=";
            default:
                return " = '".$model->escape($value)."'";
        }
    }


    public function getSQL()
    {
        $this->prepare();
        $sql = "FROM wa_contact c";

        if ($this->joins) {
            foreach ($this->joins as $join) {
                $alias = isset($join['alias']) ? $join['alias'] : '';
                if (isset($join['on'])) {
                    $on = $join['on'];
                } else {
                    $on = "c.id = ".($alias ? $alias : $join['table']).".contact_id";
                }
                $sql .= (isset($join['type']) ? " ".$join['type'] : '')." JOIN ".$join['table']." ".$alias." ON ".$on;
            }
        }

        if ($this->where) {
            $sql .= " WHERE ".implode(" AND ", $this->where);
        }

        return $sql;
    }

    /**
     * Save requested fields of the collection in temporary table
     *
     * @param string $table - name of the temporary table
     * @param string $fields - fields for select
     * @return bool - result
     */
    public function toTempTable($table, $fields = 'id')
    {
        /*
         $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS ".$table." (
                    id INT UNSIGNED NOT NULL PRIMARY KEY
         )";
         $this->getModel()->exec($sql);
         */

         $sql = "INSERT INTO ".$table." (".(is_array($fields) ? implode(",", array_keys($fields)) : $fields).")
                  SELECT DISTINCT ".(is_array($fields) ? 'c.'.implode(",c.", $fields) : 'c.'.$fields)." ".$this->getSQL().$this->getOrderBy();
         $this->getModel()->exec($sql);
    }

    /**
     * Set order by clause for select
     *
     * @param string $field
     * @param string $order
     */
    public function orderBy($field, $order = 'ASC')
    {
        if (strtolower(trim($order)) == 'desc') {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }
        $field = trim($field);
        if ($field == '~data') {
            $this->joins[] = array(
                'table' => 'wa_contact_data',
                'alias' => 'd',
                'type' => 'LEFT'
            );
            $this->group_by = 'c.id';
            return $this->order_by = 'count(*) '.$order;
        } else if ($field) {
            $contact_model = $this->getModel();
            if ($contact_model->fieldExists($field)) {
                return $this->order_by = $field." ".$order;
            }
        }
        return '';
    }
}