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
	protected $order, $where, $limit;
	
	public function __construct(waModel $model)
	{
		$this->model = $model;
	}
	
	/**
	 * @return waDbQuery 
	 */
	public function select($select)
	{
		$this->select = $select;
		return $this;
	}
	
	/**
	 * @return waDbQuery 
	 */
	public function where($where)
	{
		$this->where = $where;
		return $this;
	}
	
	/**
	 * @return waDbQuery 
	 */
	public function limit($limit)
	{
		$this->limit = $limit;
		return $this;
	}	
	
	/**
	 * @return waDbQuery 
	 */
	public function order($order)
	{
		$this->order = $order;
		return $this;
	}
	
	public function fetchAll($key = null, $normalize = false)
	{
		$sql = $this->getSQL();
		return $this->model->query($sql)->fetchAll($key, $normalize);
	}
	
	public function fetch()
	{
		$sql = $this->getSQL();
		return $this->model->query($sql)->fetch();	
	}
	
	public function fetchField($field = false, $seek = false)
	{
		$sql = $this->getSQL();
		return $this->model->query($sql)->fetchField($field, $seek);	
	}
	
	protected function getSQL()
	{
		$sql = "SELECT ".$this->select." FROM ".$this->model->getTableName();
		if ($this->where) {
			$sql .= " WHERE ".$this->where;
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
	 * @return waDbResultSelect
	 */
	protected function query()
	{
		return $this->model->query($this->getSQL());
	}
}