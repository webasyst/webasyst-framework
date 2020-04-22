<?php

/**
 * Abstract class for work with params-like model (vertical table model)
 * Just extend it and use working methods (get/getOne/set/setOne/setMap/delete)
 * Class waParamsModel
 */
abstract class waParamsModel extends waModel
{
    protected $external_id;
    protected $name_field = 'name';
    protected $value_field = 'value';
    protected $serializing = false;

    /**
     * waParamsModel constructor.
     * @param null $type
     * @param bool $writable
     * @throws waException
     */
    public function __construct($type = null, $writable = false)
    {
        parent::__construct($type, $writable);
        if (empty($this->external_id)) {
            throw new waException('External ID must be set');
        }
        if (!$this->fieldExists($this->external_id)) {
            throw new waException("External field {$this->external_id} doesn't exist");
        }
        if (!$this->fieldExists($this->name_field)) {
            throw new waException("Field {$this->name_field} doesn't exist");
        }
        if (!$this->fieldExists($this->value_field)) {
            throw new waException("Field {$this->value_field} doesn't exist");
        }
    }

    /**
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * @return string
     */
    public function getNameField()
    {
        return $this->name_field;
    }

    /**
     * @return string
     */
    public function getValueField()
    {
        return $this->value_field;
    }

    /**
     * @return bool
     */
    public function isSerializable()
    {
        return $this->serializing;
    }

    /**
     * Get params by id or ids
     *
     * @param array[]string|string $id (external IDs)
     * @return array|mixed Result depends on input param (single value or array)
     * @throws waException
     */
    public function get($id)
    {
        $ids = waUtils::toStrArray($id);
        if (!$ids) {
            return array();
        }
        $params = array_fill_keys($ids, array());
        foreach ($this->getByField($this->external_id, $ids, true) as $p) {
            $params[$p[$this->external_id]][$p[$this->name_field]] = $p[$this->value_field];
        }

        if ($this->serializing) {
            foreach ($params as $_id => &$_params) {
                $_params = $this->unserialize($_params);
            }
            unset($_params);
        }

        if (is_scalar($id)) {
            $id = strval($id);
            return isset($params[$id]) ? $params[$id] : array();
        }
        return $params;
    }

    /**
     * Get value of one param
     * @param string $id External ID
     * @param string $name
     * @return string|null
     * @throws waException
     */
    public function getOne($id, $name)
    {
        $item = $this->getByField(
            array(
                $this->external_id => $id,
                $this->name_field => $name
            )
        );
        if (!$item) {
            return null;
        }
        $value = $item[$this->value_field];
        return $this->serializing ? $this->unserializeOne($value) : $value;
    }

    /**
     * Set params
     *
     * @param array[]string|string $ids External ID(s)
     * @param array|null $params key=>value format of array or null (to delete all params assigned to form)
     * @param bool $delete_old
     * @return bool
     * @throws waException
     */
    public function set($ids, $params = array(), $delete_old = true)
    {
        if (!$ids) {
            return false;
        }

        $ids = waUtils::toStrArray($ids);

        if (is_null($params) || $delete_old) {
            // remove all old params
            $this->deleteByField(array(
                $this->external_id => $ids,
            ));
        } elseif ($params) {
            // Remove selected set of params only
            $this->deleteByField(array(
                $this->external_id => $ids,
                'name' => array_keys($params),
            ));
        }

        if (!$params) {
            return true;
        }

        $params = $this->cleanDataBeforeSet($params);

        if ($this->serializing) {
            $params = $this->serialize($params);
        }

        // accumulate param rows to add
        $add_params = array();
        foreach ($params as $name => $value) {
            foreach ($ids as $_id) {
                $add_params[] = array(
                    'name' => $name,
                    'value' => $value,
                    $this->external_id => $_id,
                );
            }
        }

        // add new params
        if ($add_params) {
            $this->multipleInsert($add_params);
        }

        return true;
    }

    /**
     * Recursively cleaning of data
     * @param $params
     * @return mixed
     */
    protected function cleanDataBeforeSet($params)
    {
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                $params[$key] = $this->cleanDataBeforeSet($param);
            } elseif (is_scalar($param)) {
                $param = (string)$param;
                if (strlen($param) <= 0) {
                    unset($params[$key]);
                }
            } else {
                // object and some other trash don't save
                unset($params[$key]);
            }
        }
        return $params;
    }

    /**
     * @param array $map Map of format <external_id> => <params>
     * @param bool $delete_old
     * @throws waException
     */
    public function setMap($map, $delete_old = true)
    {
        foreach ($map as $id => $params) {
            if (is_scalar($id)) {
                $this->set($id, $params, $delete_old);
            }
        }
    }

    /**
     * Set value for one param
     * @param string $id External ID
     * @param string $name
     * @param string $value
     * @return bool
     * @throws waException
     */
    public function setOne($id, $name, $value)
    {
        return $this->set($id, array($name => $value), false);
    }

    /**
     * @param array[]string|string $id
     * @return bool
     * @throws waException
     */
    public function delete($id)
    {
        return $this->set($id, null);
    }

    /**
     * @param array $params
     * @return array
     */
    protected function serialize(array $params)
    {
        foreach ($params as $key => &$value) {
            $value = $this->serializeOne($value);
        }
        unset($value);
        return $params;
    }

    /**
     * @param $value
     * @return bool|false|float|int|string
     */
    protected function serializeOne($value)
    {
        return is_scalar($value) ? $value : json_encode($value);
    }

    /**
     * @param array $params
     * @return array
     */
    protected function unserialize(array $params)
    {
        foreach ($params as $key => &$value) {
            $value = $this->unserializeOne($value);
        }
        unset($value);
        return $params;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function unserializeOne($value)
    {
        if ($value && ($value[0] == '{' || $value[0] == '[' || $value == 'null')) {
            $value = json_decode($value, true);
        }
        return $value;
    }
}
