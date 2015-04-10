<?php

class waContactsCollection
{
    protected $hash;

    protected $fields = array();
    protected $order_by = '';
    protected $group_by;
    protected $having = array();
    protected $where;
    protected $where_fields = array();
    protected $joins;
    protected $join_index = array();
    protected $info = array();

    protected $title = '';
    protected $count;

    protected $options = array(
    );

    protected $update_count;


    protected $post_fields;
    protected $prepared;

    protected $alias_index = array();

    protected $models;

    protected $left_joins = array();

    /**
     * Constructor for collection of contacts
     *
     * @param string $hash - search hash
     * @param array $options
     * @example
     *     All contacts where name contains John
     *     $collection = new waContactsCollection('/search/name*=John/');
     *
     *     All contacts in the list with id 100500
     *     $collection = new waContactsCollection('/list/100500/');
     *
     *     Contacts with ids from list
     *     $collection = new waContactsCollection('/id/1,10,100,500/');
     *     or
     *     $collection = new waContactsCollection('/search/id=1,10,100,500/');
     *
     *     All contacts
     *     $collection = new waContactsCollection();
     */
    public function __construct($hash = '', $options = array())
    {
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
        $this->setHash($hash);
    }

    public function setHash($hash)
    {
        if (is_array($hash)) {
            $hash = '/id/'.implode(',', $hash);
        }
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
        $this->hash = explode('/', $this->hash, 2);

        if (isset($this->hash[1])) {
            $escapedS = 'ESCAPED_BACKSLASH';
            while(FALSE !== strpos($this->hash[1], $escapedS)) {
                $escapedS .= rand(0, 9);
            }
            $this->hash[1] = str_replace('\/', $escapedS, $this->hash[1]);
        }

        if ($this->hash[0] !== 'search' && isset($this->hash[1]) && strpos($this->hash[1], '/')) {
            $this->hash[1] = substr($this->hash[1], 0, strpos($this->hash[1], '/'));
        }

        if (isset($this->hash[1])) {
            $this->hash[1] = str_replace($escapedS, '/', $this->hash[1]);
        }
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
            if ($this->getHaving()) {
                $sql .= $this->getGroupBy();
                $sql .= $this->getHaving();
                $sql = "SELECT COUNT(t.id) FROM (SELECT c.id {$sql}) t";
            } else {
                $sql = "SELECT COUNT(".($this->joins ? 'DISTINCT ' : '')."c.id) ".$sql;
            }
            //header("X-SQL-COUNT:". $sql);
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

    public function addField($field, $alias)
    {
        $this->fields[$alias] = $field;
    }

    protected function getFields($fields)
    {
        $contact_model = $this->getModel();
        if (substr($fields, 0, 1) == '*') {
            $extra_fileds = substr($fields, 2);
            $fields = $contact_model->getMetadata();
            unset($fields['password']);

            $fields = array_keys($fields);
            foreach ($fields as &$f) {
                $f = 'c.'.$f;
            }
            unset($f);

            $this->post_fields['_internal'] = array('_online_status');
            $this->post_fields['email'] = array('email');
            $this->post_fields['data'] = array();

            if ($extra_fileds) {
                $extra_fileds = $this->getFields($extra_fileds);
                $fields = array_merge($fields, explode(",", $extra_fileds));
                $fields = array_unique($fields);
            }

            $result = implode(",", $fields);
            foreach($this->fields as $alias => $expr) {
                $result .= ",".$expr.' AS '.$alias;
            }

            return $result;
        }

        $required_fields = array('id' => 'c', 'is_company' => 'c'); // field => table, to be added later in any case

        if (!is_array($fields)) {
            $fields = explode(",", $fields);
        }

        // Add required fields to select and delete fields for getting data after query
        foreach ($fields as $i => $f) {
            if (!$contact_model->fieldExists($f)) {
                if ($f == 'email') {
                    $this->post_fields['email'][] = $f;
                } elseif ($f == '_online_status') {
                    $required_fields['last_datetime'] = 'c';
                    $required_fields['login'] = 'c';
                    $this->post_fields['_internal'][] = $f;
                } elseif ($f == '_access') {
                    $this->post_fields['_internal'][] = $f;
                } elseif ($f == 'photo_url' || substr($f, 0, 10) == 'photo_url_') {
                    $required_fields['photo'] = 'c';
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

        foreach($this->fields as $alias => $expr) {
            $fields[] = $expr.' AS '.$alias;
        }
        return implode(",", $fields);
    }

    /**
     * Get data for contacts in this collection.
     * @param string|array $fields
     * @param int $offset
     * @param int $limit
     * @return array [contact_id][field] = field value in appropriate field format
     * @throws waException
     */
    public function getContacts($fields = "id", $offset = 0, $limit = 50)
    {
        $sql = "SELECT ".$this->getFields($fields)." ".$this->getSQL();
        $sql .= $this->getGroupBy();
        $sql .= $this->getHaving();
        $sql .= $this->getOrderBy();
        $sql .= " LIMIT ".($offset ? $offset.',' : '').(int)$limit;
        //header("X-SQL-". mt_rand() . ": ". str_replace("\n", " ", $sql));
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
                /**
                 * @var waContactField $field
                 */
                $fill[$field->getStorage(true)][$fid] = false;
            }

            foreach ($this->post_fields as $table => $fields) {
                if ($table == '_internal') {
                    foreach ($fields as $f) {
                        /**
                         * @var $f string
                         */
                        if ($f == 'photo_url' || substr($f, 0, 10) == 'photo_url_') {
                            if ($f == 'photo_url') {
                                $size = null;
                            } else {
                                $size = substr($f, 10);
                            }
                            $retina = isset($this->options['photo_url_2x']) ? $this->options['photo_url_2x'] : null;
                            foreach ($data as $id => &$v) {
                                $v[$f] = waContact::getPhotoUrl($id, $v['photo'], $size, $size, $v['is_company'] ? 'company' : 'person', $retina);
                            }
                            unset($v);
                        } else {
                            switch($f) {
                                case '_online_status':

                                    $llm = new waLoginLogModel();
                                    $contact_ids_map = $llm->select('DISTINCT contact_id')->where('datetime_out IS NULL')->fetchAll('contact_id');
                                    $timeout = waUser::getOption('online_timeout');
                                    foreach($data as &$v) {
                                        if (isset($v['last_datetime']) && $v['last_datetime'] && $v['last_datetime'] != '0000-00-00 00:00:00') {
                                              if (time() - strtotime($v['last_datetime']) < $timeout) {
                                                  if (isset($contact_ids_map[$v['id']])) {
                                                      $v['_online_status'] = 'online';
                                                  } else {
                                                      $v['_online_status'] = 'offline';
                                                  }
                                              }
                                          }
                                          $v['_online_status'] = 'offline';
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
                    }
                    continue;
                }

                $data_fields = $fields;
                foreach ($data_fields as $k => $field_id) {
                    $f = waContactFields::get($field_id);
                    if ($f && $f instanceof waContactCompositeField) {
                        unset($data_fields[$k]);
                        $data_fields = array_merge($data_fields, $f->getField());
                    }
                }

                $model = $this->getModel($table);
                $post_data = $model->getData($ids, $data_fields);
                foreach ($post_data as $contact_id => $contact_data) {
                    foreach ($contact_data as $field_id => $value) {
                        if (!($f = waContactFields::get($field_id))) {
                            continue;
                        }
                        if (!$f->isMulti()) {
                            $post_data[$contact_id][$field_id] = isset($value[0]['data']) ? $value[0]['data'] :
                                (is_array($value[0]) ? $value[0]['value'] : $value[0]);
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


    public function prepare($new = false, $auto_title = true)
    {
        if (!$this->prepared || $new) {
            $type = $this->hash[0];
            if ($type) {
                $method = strtolower($type).'Prepare';
                if (method_exists($this, $method)) {
                    $this->$method(isset($this->hash[1]) ? $this->hash[1] : '', $auto_title);
                } else {
                    $params = array(
                        'collection' => $this,
                        'auto_title' => $auto_title,
                        'new'        => $new,
                    );
                    /**
                * @event contacts_collection
                * @param array [string]mixed $params
                * @param array [string]waContactsCollection $params['collection']
                * @param array [string]boolean $params['auto_title']
                * @param array [string]boolean $params['new']
                * @return bool null if ignored, true when something changed in the collection
                */
                    $processed = wa()->event(array('contacts', 'contacts_collection'), $params);
                    if (!$processed) {
                        $this->where[] = 0;
                    }
                }
            } elseif ($auto_title) {
                $this->addTitle(_ws('All contacts'));
            }

            if ($this->prepared) {
                return;
            }
            $this->prepared = true;
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

        //$query = urldecode($query);   // sometime this urldecode broke query, better make urldecode (if needed) outside the searchPrepare

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
            $part = str_replace(array($escapedBS, $escapedAmp), array('\\', '&'), $part);
            $parts = preg_split("/(\\\$=|\^=|\*=|==|!=|>=|<=|=|>|<|@=)/uis", $part, 2, PREG_SPLIT_DELIM_CAPTURE);

            if ($parts) {
                if ($parts[0] === 'name' && $parts[1] === '*=') {
                    $t_a = preg_split("/\s+/", $parts[2]);
                    $cond = array();
                    foreach ($t_a as $t) {
                        $t = trim($t);
                        if ($t) {
                            $t = $model->escape($t, 'like');
                            $cond[] = "c.name LIKE '%{$t}%'";
                        }
                    }
                    $this->addWhere(implode(" AND ", $cond));
                    $title[] = _ws('Name').$parts[1].$parts[2];
                } else if ($parts[0] == 'email') {
                    if (!isset($this->joins['email'])) {
                        $this->joins['email'] = array(
                            'table' => 'wa_contact_emails',
                            'alias' => 'e'
                        );
                    }
                    $title[] = waContactFields::get($parts[0])->getName().$parts[1].$parts[2];
                    $this->where[] = 'e.email'.$this->getExpression($parts[1], $parts[2]);
                } else if ($model->fieldExists($parts[0])) {
                    if ($f = waContactFields::get($parts[0])) {
                        $title[] = $f->getName().$parts[1].$parts[2];
                    } else {
                        $title[] = $parts[0].$parts[1].$parts[2];
                    }
                    $this->where[] = 'c.'.$parts[0].$this->getExpression($parts[1], $parts[2]);
                } else if ($parts[0] == 'category') {
                    if (!isset($this->joins['categories'])) {
                        $this->joins['categories'] = array(
                            'table' => 'wa_contact_categories',
                            'alias' => 'cc'
                        );
                    }
                    $title[] = _ws('Category').$parts[1].$parts[2];
                    $this->where[] = 'cc.category_id'.$this->getExpression($parts[1], $parts[2]);

                } else {
                    $field_parts = explode('.', $parts[0]);
                    $f = $field_parts[0];
                    if ($fo = waContactFields::get($f)) {
                        $title[] = $fo->getName().$parts[1].$parts[2];
                    }
                    $ext = isset($field_parts[1]) ? $field_parts[1] : null;
                    $on = ":table.contact_id = c.id AND :table.field = '".$model->escape($f)."'";
                    $this->where_fields[] = $f;

                    $op = $parts[1];
                    $term = $parts[2];

                    if ($f === 'address:country') {

                        $al1 = $this->addJoin('wa_contact_data', $on);
                        $whr = "{$al1}.value ".$this->getExpression($op, $term);
                        if ($ext !== null) {
                            $whr .= " AND {$al1}.ext = '".$model->escape($ext)."'";
                            $whr = "({$whr})";
                        }

                        // search by l18n name of countries
                        if ($op === '*=') {
                            if (wa()->getLocale() === 'en_US') {
                                $al2 = $this->addLeftJoin('wa_country', ":table.iso3letter = {$al1}.value");
                                $whr .= " OR {$al2}.name ".$this->getExpression($parts[1], $parts[2]);
                            } else if (wa()->getLocale() !== 'en_US') {
                                $iso3letters = array();
                                $country_model = new waCountryModel();
                                $countries = $country_model->all();
                                $term = mb_strtolower($term);
                                foreach ($countries as &$cntr) {
                                    if (mb_strpos(mb_strtolower($cntr['name']), $term) === 0) {
                                        $iso3letters[] = $cntr['iso3letter'];
                                    }
                                }
                                unset($cntr);

                                if ($iso3letters) {
                                    $al2 = $this->addLeftJoin('wa_country', ":table.iso3letter = {$al1}.value");
                                    $whr .= " OR {$al2}.iso3letter IN ('".implode("','", $iso3letters)."')";
                                }
                            }
                        }

                        $this->addWhere($whr);

                    } else if ($f === 'address:region') {

                        if (strpos($term, ":") !== false) {
                            // country_code : region_code - search by country code AND region code AND only in wa_region
                            $term = explode(":", $term);
                            $country_iso3 = $model->escape($term[0]);
                            $code = $model->escape($term[1]);
                            $al1 = $this->addJoin('wa_contact_data', $on);
                            $whr = array();
                            if ($ext !== null) {
                                $whr[] = "{$al1}.ext = '".$model->escape($ext)."'";
                            }
                            $al2 = $this->addJoin('wa_contact_data', ":table.contact_id = c.id AND :table.field = 'address:country'");
                            $al3 = $this->addJoin('wa_region', ":table.code = {$al1}.value AND :table.country_iso3 = {$al2}.value");
                            $whr[] = "{$al3}.country_iso3 = '{$country_iso3}'";
                            $whr[] = "{$al3}.code = '{$code}'";
                            $whr = implode(" AND ", $whr);
                        } else {
                            $al1 = $this->addJoin('wa_contact_data', $on);
                            $whr = "{$al1}.value".$this->getExpression($op, $term);
                            if ($ext !== null) {
                                $whr .= " AND {$al1}.ext = '".$model->escape($ext)."'";
                                $whr = "({$whr})";
                            }
                            if ($op === "*=") {
                                // if search by like, search by wa_region.name but taking into account country
                                $al2 = $this->addJoin('wa_contact_data', ":table.contact_id = c.id AND :table.field = 'address:country'");
                                $al3 = $this->addLeftJoin('wa_region', ":table.code = {$al1}.value AND :table.country_iso3 = {$al2}.value");
                                $whr .= " OR {$al3}.name ".$this->getExpression($op, $term);
                            }
                        }
                        $this->addWhere($whr);

                    } else {
                        $on .= ' AND :table.value '.$this->getExpression($op, $term);
                        if ($ext !== null) {
                            $on .= " AND :table.ext = '".$model->escape($ext)."'";
                        }
                        $this->addJoin('wa_contact_data', $on);
                    }

                }
            }
        }

        if ($title) {
            $title = implode(', ', $title);

            // Strip slashes from search title.
            $bs = '\\\\';
            $title = preg_replace("~{$bs}(_|%|&|{$bs})~", '\1', $title);
        }
        if ($auto_title && $title) {
            $this->addTitle($title, ' ');
        }
    }


    public function addTitle($title, $delim = ', ')
    {
        if (!$title && $title !== '0' && $title !== 0) {
            return;
        }
        if ($this->title) {
            $this->title .= $delim;
        }
        $this->title .= $title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getWhereFields()
    {
        return $this->where_fields;
    }

    protected function categoryPrepare($id, $auto_title = false)
    {

        $category_model = new waContactCategoryModel();
        $category = $category_model->getById($id);

        if ($category) {
            if ($auto_title) {
                $this->title = $category['name'];
            }
            $this->update_count = array(
                'model' => $category_model,
                'id' => $id,
                'count' => isset($category['cnt']) ? $category['cnt'] : 0
            );
        }

        $this->addJoin('wa_contact_categories', null, ':table.category_id = '.(int)$id);
    }

    protected function usersPrepare($params, $auto_title = true)
    {
        $this->where[] = 'c.is_user = 1';
        if ($auto_title) {
            $this->addTitle(_ws('All users'));
        }
    }

    /**
     * Add joins and conditions for hash /group/$group_id
     * @param int $id
     */
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


    /**
     * Returns ORDER BY clause
     * @return string
     */
    protected function getOrderBy()
    {
        if ($this->order_by) {
            return " ORDER BY ".$this->order_by;
        } else {
            return "";
        }
    }

    /**
     * Returns GROUP BY clause
     * @return string
     */
    protected function getGroupBy()
    {
        if ($this->group_by) {
            return " GROUP BY ".$this->group_by;
        } else {
            return "";
        }
    }

    protected function getHaving()
    {
        if ($this->having) {
            return " HAVING " . implode(' AND ', $this->having);
        } else {
            return "";
        }
    }



    /**
     * Returns contacts model
     *
     * @param string $type
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

    /**
     * Returns expression for SQL
     *
     * @param string $op - operand ==, >=, etc
     * @param string $value - value
     * @return string
     */
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
                return " LIKE '".$model->escape($value, 'like')."%'";
            case "$=":
                return " LIKE '%".$model->escape($value, 'like')."'";
            case "*=":
                return " LIKE '%".$model->escape($value, 'like')."%'";
            case '@=':
                $values = array();
                foreach (explode(',', $value) as $v) {
                    $values[] = "'".$model->escape($v)."'";
                }
                return ' IN ('.implode(',', $values).')';
            case "==":
            case "=";
            default:
                return " = '".$model->escape($value)."'";
        }
    }

    public function getSQL($with_primary_email = false)
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
                $sql .= (isset($join['type']) ? " ".$join['type'] : '')." JOIN ".$join['table']." ".$alias;
                if (isset($join['force_index'])) {
                    $sql .= ' FORCE INDEX ('.$join['force_index'].')';
                }
                $sql .= " ON ".$on;
            }
        }

        if ($with_primary_email) {
            $sql .= " JOIN wa_contact_emails _e ON c.id = _e.contact_id";
        }

        if ($this->left_joins) {
            foreach ($this->left_joins as $join) {
                $alias = isset($join['alias']) ? $join['alias'] : '';
                if (isset($join['on'])) {
                    $on = $join['on'];
                } else {
                    $on = "c.id = ".($alias ? $alias : $join['table']).".contact_id";
                }
                $sql .= (isset($join['type']) ? " ".$join['type'] : '')." LEFT JOIN ".$join['table']." ".$alias;
                if (isset($join['force_index'])) {
                    $sql .= ' FORCE INDEX ('.$join['force_index'].')';
                }
                $sql .= " ON ".$on;
            }
        }

        if ($this->where) {
            $where = $this->where;
            if ($with_primary_email) {
                $where[] = "_e.sort = 0";
            }
            if (!empty($where['_or'])) {
                $where['_or'] = "(" . implode(" OR ", $where['_or']) . ")";
            }
            $sql .= " WHERE ".implode(" AND ", $where);
        }

        return $sql;
    }

    /**
     * Save requested fields of the collection in temporary table
     *
     * @param string $table - name of the temporary table
     * @param string $fields - fields for select
     * @param bool $ignore
     * @return bool - result
     */
    public function saveToTable($table, $fields = 'id', $ignore = false)
    {
        if (!is_array($fields)) {
            $fields = explode(",", $fields);
            $fields = array_map("trim", $fields);
        }
        $primary_email = false;
        $insert_fields = $select_fields = array();
        foreach ($fields as $k => $v) {
            if (is_numeric($k)) {
                $insert_fields[] = $v;
                $select_fields[] = "c.".$v;
            } else {
                $insert_fields[] = $k;
                if (strpos($v, '.') !== false || is_numeric($v)) {
                    $select_fields[] = $v;
                } else {
                    if ($v == '_email') {
                        $select_fields[] = "_e.email";
                        $primary_email = true;
                    } else {
                        $select_fields[] = "c.".$v;
                    }
                }
            }

        }
        $sql = "INSERT ".($ignore ? "IGNORE " : "")."INTO ".$table." (".implode(",", $insert_fields).")
                SELECT DISTINCT ".implode(",", $select_fields)." ".$this->getSQL($primary_email).$this->getOrderBy();
        return $this->getModel()->exec($sql);
    }

    /**
     * Set order by clause for select
     *
     * @param string $field
     * @param string $order
     * @return string
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
            $this->fields['data_count'] = 'count(*)';
            $this->joins[] = array(
                'table' => 'wa_contact_data',
                'alias' => 'd',
                'type' => 'LEFT'
            );
            $this->group_by = 'c.id';
            $this->order_by = 'data_count '.$order;
        } else if ($field) {
            $field = trim($field);
            if (substr($field, 0, 2) == 'c.') {
                $field = substr($field, 2);
            }
            if (!$this->getModel()->fieldExists($field)) {
                $field = 'id';
            }
            $this->order_by = 'c.'.$field." ".$order;
        }
        return $this->order_by;
    }

    public function addJoin($table, $on = null, $where = null, $options = array())
    {
        $type = '';
        if (is_array($table)) {
            if (isset($table['on'])) {
                $on = $table['on'];
            }
            if (isset($table['where'])) {
                $where = $table['where'];
            }
            if (isset($table['type'])) {
                $type = $table['type'];
            }
            $table = $table['table'];
        }

        $alias = $this->getTableAlias($table);

        if (!isset($this->join_index[$alias])) {
            $this->join_index[$alias] = 1;
        } else {
            $this->join_index[$alias]++;
        }
        $alias .= $this->join_index[$alias];

        $join = array(
            'table' => $table,
            'alias' => $alias,
            'type' => $type
        );
        if (!empty($options['force_index'])) {
            $join['force_index'] = $options['force_index'];
        }
        if ($on) {
            $join['on'] = str_replace(':table', $alias, $on);
        }
        $this->joins[] = $join;
        if ($where) {
            $this->addWhere(str_replace(':table', $alias, $where));
        }
        return $alias;
    }

    public function addLeftJoin($table, $on = null, $where = null, $options = array())
    {
        $type = '';
        if (is_array($table)) {
            if (isset($table['on'])) {
                $on = $table['on'];
            }
            if (isset($table['where'])) {
                $where = $table['where'];
            }
            if (isset($table['type'])) {
                $type = $table['type'];
            }
            $table = $table['table'];
        }

        $alias = $this->getTableAlias($table);

        if (!isset($this->join_index[$alias])) {
            $this->join_index[$alias] = 1;
        } else {
            $this->join_index[$alias]++;
        }
        $alias .= $this->join_index[$alias];

        $join = array(
            'table' => $table,
            'alias' => $alias,
            'type' => $type
        );
        if (!empty($options['force_index'])) {
            $join['force_index'] = $options['force_index'];
        }
        if ($on) {
            $join['on'] = str_replace(':table', $alias, $on);
        }
        $this->left_joins[] = $join;
        if ($where) {
            $this->addWhere(str_replace(':table', $alias, $where));
        }
        return $alias;
    }

    public function setGroupBy($group_by)
    {
        $this->group_by = $group_by;
    }

    public function addWhere($condition, $or = false)
    {
        $p = strpos(strtolower($condition), ' or ');
        if ($or) {
            if (!isset($this->where['_or'])) {
                $this->where['_or'] = array();
            }
            $where = &$this->where['_or'];
        } else {
            $where = &$this->where;
        }
        if ($p !== false) {
            $where[] = "({$condition})";
        } else {
            $where[] = $condition;
        }
        return $this;
    }

    public function addHaving($condition)
    {
        $this->having[] = $condition;
    }

    public function getJoinedAlias($table)
    {
        $alias = $this->getTableAlias($table);
        return $alias.$this->join_index[$alias];
    }

    protected function getTableAlias($table)
    {
        $t = explode('_', $table);
        $alias = '';
        foreach ($t as $tp) {
            if ($tp == 'hub') {
                continue;
            }
            $alias .= substr($tp, 0, 1);
        }
        if (!$alias) {
            $alias = $table;
        }
        return $alias;
    }

    public function getHash($params_only = false)
    {
        if ($params_only) {
            return $this->hash[1];
        } else {
            return $this->hash;
        }
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function setInfo($info)
    {
        $this->info = $info;
    }

    public function getUpdateCount()
    {
        return $this->update_count;
    }

    public function setUpdateCount($update_count)
    {
        $this->update_count = $update_count;
    }
}