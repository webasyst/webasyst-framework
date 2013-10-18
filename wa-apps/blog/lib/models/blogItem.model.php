<?php
abstract class blogItemModel extends waModel
{
    protected $where_conditions;
    protected $sql_params = array();
    protected $extend_options = array();
    protected $extend_data = array();
    private $limit = 10;
    private $search_count;
    protected $url_length = 255;

    protected $obligatory_fields = array();

    public function checkUrl($url, $id = null)
    {

        $url = preg_replace('/[\?#\/\s_]+/', '_', $url);

        $where = array("(url='{$this->escape($url)}')");
        if ($id) {
            $where[] = "({$this->id} != {$this->escape($id)})";
        }

        return $this->select('id')->where(implode(' AND ', $where))->fetch();
    }

    private static function shortUuid()
    {
        static $time = 0;
        static $counter = 0;
        if (!$time) {
            $time = time();
        }
        return ($time << 24) + $counter++;
    }


    public function genUniqueUrl($from)
    {
        static $time = 0;
        static $counter = 0;
        $from = preg_replace('/\s+/', '-', $from);
        $url = blogHelper::transliterate($from);

        if (strlen($url) == 0) {
            $url = self::shortUuid();
        } else {
            $url = mb_substr($url, 0, $this->url_length);
        }
        $url = mb_strtolower($url);

        $pattern = mb_substr($this->escape($url, 'like'), 0, $this->url_length - 3).'%';
        $sql = "SELECT url FROM {$this->table} WHERE url LIKE '{$pattern}' ORDER BY LENGTH(url)";

        $alike = $this->query($sql)->fetchAll('url');

        if (is_array($alike) && isset($alike[$url])) {
            $last = array_shift($alike);
            $counter = 1;
            do {
                $modifier = "-{$counter}";
                $length = mb_strlen($modifier);
                $url = mb_substr($last['url'], 0, $this->url_length - $length).$modifier;
            } while (($counter++ < 99) && isset($alike[$url]));
            if (isset($alike[$url])) {
                $short_uuid = self::shortUuid();
                $length = mb_strlen($short_uuid);

                $url = mb_substr($last['url'], 0, $this->url_length - $length).$short_uuid;
            }
        }

        return mb_strtolower($url);
    }

    public function search($options = array(), $extend_options = array(), $extend_data = array())
    {
        $this->sql_params = array();
        $this->extend_options = (array)$extend_options;
        $this->extend_data = $extend_data;
    }

    protected function buildSearchSQL($fields = array(), $count = false)
    {
        $join = '';
        $select_fields = $this->setFields($fields);
        if (isset($this->sql_params['join'])) {
            foreach ($this->sql_params['join'] as $table => $options) {
                if (is_array($options['condition'])) {
                    $options['condition'] = implode(' AND ', $options['condition']);
                }
                $type = isset($options['type']) ? strtoupper($options['type']).' ' : '';
                $join .= "\n{$type}JOIN {$table} ON {$options['condition']}";

                if (isset($options['fields']) && $options['fields']) {
                    if (is_array($options['fields'])) {
                        foreach ($options['fields'] as $field => $alias) {
                            if (is_int($field)) {
                                $field = $alias;
                            }
                            $select_fields[] = "{$table}.{$field} AS '{$alias}'";
                        }
                    } else {
                        $select_fields[] = "{$table}.*";
                    }
                }

                if (isset($options['values']) && $options['values']) {
                    if (is_array($options['values'])) {
                        foreach ($options['values'] as $alias => $value) {
                            $select_fields[] = $this->escape($value)." AS '{$alias}'";
                        }
                    } else {
                        $select_fields[] = "{$table}.*";
                    }
                }

            }
        }

        if ($count) {
            $count_sql = "SELECT COUNT(".($join ? 'DISTINCT ' : '').$this->table.".id) FROM {$this->table} {$join}";
        }

        $sql = "SELECT ".($join ? 'DISTINCT ' : '').implode(', ', $select_fields)."
            FROM {$this->table} {$join}";
        if (isset($this->sql_params['where'])) {
            $where = implode(' AND ', $this->sql_params['where']);
            if ($where) {
                $sql .= "\n WHERE {$where}";
                if ($count) {
                    $count_sql .= "\n WHERE {$where}";
                }
            }
            unset($this->sql_params['where']);
        }

        if (isset($this->sql_params['order']) && $this->sql_params['order']) {
            $sql .= "\n ORDER BY {$this->sql_params['order']}";
            unset($this->sql_params['order']);
        }
        if ($count) {
            return array($sql, $count_sql);
        }
        return $sql;

    }

    protected function setFields($fields = array(), $add_table = true)
    {
        $select_fields = array();
        if ($fields) {
            if (!is_array($fields)) {
                $fields = array_map('trim', explode(',', $fields));
            }
            $fields[] = $this->id;
            $fields = array_unique($fields);
            foreach ($fields as $id => $field) {
                if (is_int($id)) {
                    $select_fields[$field] = $add_table ? "{$this->table}.{$field}" : $field;
                } else {
                    if (strpos($field, '.') === false) {
                        $field = $add_table ? "{$this->table}.{$field}" : $field;
                    }
                    $select_fields[$id] = "{$field} as '{$id}'";
                }
            }
        } elseif ($fields === false) {
            $select_fields = array();
            foreach ($this->fields as $field => $info) {
                if (!in_array(strtolower($info['type']), array('blob', 'text'))) {
                    $select_fields[$field] = $add_table ? "{$this->table}.{$field}" : $field;
                }
            }
        } else {
            $select_fields[] = $add_table ? "{$this->table}.*" : '*';
        }
        return $select_fields;
    }

    abstract public function prepareView($items, $options = array(), $extend_data = array());

    /**
     * @param array|boolean $fields
     * @return mixed
     */
    public function fetchSearchAll($fields = array())
    {
        $sql = $this->buildSearchSQL($fields);
        $items = $this->query($sql, $this->sql_params)->fetchAll($this->id);
        $this->search_count = count($items);
        return $this->prepareView($items);
    }

    public function fetchSearchItem($fields = array())
    {
        if (isset($this->sql_params['order'])) {
            unset($this->sql_params['order']);
        }
        if (isset($this->sql_params['sort'])) {
            unset($this->sql_params['sort']);
        }
        $sql = $this->buildSearchSQL($fields);
        $sql .= "\n LIMIT 1";
        $items = $this->query($sql, $this->sql_params)->fetchAll($this->id);

        $this->search_count = count($items);

        $items = $this->prepareView($items);
        reset($items);
        return current($items);
    }

    public function fetchSearch($offset, $limit, $fields = array())
    {
        $this->limit = $limit;
        $this->sql_params['offset'] = max(0, $offset);
        $this->sql_params['limit'] = $this->limit;

        $sqls = $this->buildSearchSQL($fields, true);
        $sql = $sqls[0];
        $count_sql = $sqls[1];
        $sql .= "\n LIMIT i:offset, i:limit";
        $items = $this->query($sql, $this->sql_params)->fetchAll($this->id);

        $this->search_count = $this->query($count_sql, $this->sql_params)->fetchField();

        return $this->prepareView($items);
    }

    public function fetchSearchPage($page, $item_per_page = 10, $fields = array())
    {
        $this->limit = max(1, $item_per_page);
        $this->sql_params['offset'] = max(0, ($page - 1) * $this->limit);
        $this->sql_params['limit'] = $this->limit;

        $sqls = $this->buildSearchSQL($fields, true);
        $sql = $sqls[0];
        $count_sql = $sqls[1];
        $sql .= "\n LIMIT i:offset, i:limit";
        $items = $this->query($sql, $this->sql_params)->fetchAll($this->id);

        $this->search_count = $this->query($count_sql, $this->sql_params)->fetchField();

        return $this->prepareView($items);
    }

    public function searchCount()
    {
        return $this->search_count;
    }

    public function pageCount()
    {
        return ceil($this->search_count / $this->limit);
    }
}