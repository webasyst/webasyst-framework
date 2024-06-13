<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage database
 */
class waDbQuery
{
    /**
     * @var waModel
     */
    protected $model;

    protected $select = '*';
    protected $order, $limit;
    protected $where = array();

    public function __construct(waModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param string $select
     * @return waDbQuery
     */
    public function select($select)
    {
        $this->select = $select;
        return $this;
    }

    /**
     * @param mixed $where
     * @return waDbQuery
     */
    public function where($where)
    {
        $params = func_get_args();
        $where = array_shift($params);
        if ($params) {
            $statement = new waDbStatement($this->model, $where);
            if (is_array($params[0])) {
                $statement->bindArray($params[0]);
            } else {
                $statement->bindArray($params);
            }
            $this->where[] = $statement->getQuery();
        } elseif($where) {
            $this->where[] = $where;
        }
        return $this;
    }

    /**
     * @param string $limit
     * @return waDbQuery
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param string $order
     * @return waDbQuery
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    public function fetchAll($key = null, $normalize = false)
    {
        return $this->query()->fetchAll($key, $normalize);
    }

    public function fetch()
    {
        return $this->query()->fetch();
    }

    public function fetchAssoc()
    {
        return $this->query()->fetchAssoc();
    }

    public function fetchField($field = false, $seek = false)
    {
        return $this->query()->fetchField($field, $seek);
    }

    public function getSQL()
    {
        $sql = "SELECT ".$this->select." FROM ".$this->model->getTableName();
        if ($this->where) {
            $sql .= " WHERE (".implode(") AND (", $this->where).")";
        }
        if ($this->order) {
            $sql .= " ORDER BY ".$this->order;
        }
        if ($this->limit) {
            $sql .= " LIMIT ".$this->limit;
        }
        return $sql;
    }

    /**
     * @return string
     * @since 2.6.2
     */
    public function getQuery()
    {
        return $this->getSQL();
    }

    /**
     * @return waDbResultSelect
     */
    public function query()
    {
        return $this->model->query($this->getQuery());
    }
}
