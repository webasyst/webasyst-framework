<?php
class stickiesModel extends waModel
{
    /**
     *
     * @param $condition
     * @param $fields
     * @param array|string $order_fields
     * @param bool $all
     * @internal param $order
     * @return array
     */
	public function getFieldsByField($condition, $fields = null,$order_fields = null, $all = true)
	{
		$select_fields = $this->buildSelectChunk($fields);

		$sql = "SELECT {$select_fields} FROM `{$this->table}`";
		if($where = $this->buildWhereChunk($condition)){
			$sql .= "\nWHERE {$where}";
		}
		if($order = $this->buildOrderChunk($order_fields)){
			$sql .= "\nORDER BY {$order}";
		}
		$result = $this->query($sql);
		if ($all) {
			return $result->fetchAll(is_string($all) ? $all : null);
		} else {
			return $result->fetch();
		}
	}

	private function buildOrderChunk($order_fields)
	{
		$order = array();
		if($order_fields){
			foreach ((array)$order_fields as $f => $d) {
				if (!isset($this->fields[$f])) {
					throw new waDbException(sprintf('Unknown field %s', $f));
				}
				$d = strtolower($d);
				$order[] = "`{$f}` ".(($d=='desc')?'DESC':'ASC');
					
			}
		}
		return implode(", ", $order);
	}

    /**
     *
     * @param $fields array
     * @throws waDbException
     * @return string
     */
	private function buildWhereChunk($fields)
	{
		$where = array();
		if($fields){
			foreach ((array)$fields as $f => $v) {
				if (!isset($this->fields[$f])) {
					throw new waDbException(sprintf('Unknown field %s', $f));
				}
				if (is_array($v)) {
					$where[] = "`{$f}` IN (".implode("','", $this->escape($v))."')";
				} else {
					$where[] = "`{$f}` = ".$this->getFieldValue($f, $v);
				}
			}
		}
		return implode("\n AND ", $where);
	}

	private function buildSelectChunk($fields = null)
	{

		if($fields){
			if(is_string($fields)){
				$fields = explode(',',$fields);
				$fields = array_map('trim',$fields);
			}
			$select_fields = array($this->id);
			foreach((array)$fields as $field){
				if($this->fieldExists($field)){
					$select_fields[] = $field;
				}
			}
			$select_fields = array_unique($select_fields);
			$select_fields = '`'.implode('`, `',$select_fields).'`';

		}else{
			$select_fields = '*';
		}
		return $select_fields;
	}
}
