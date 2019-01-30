<?php

class webasystSettingsFieldConditionalValuesSaveController extends webasystSettingsJsonController
{
    /**
     * @var waContactFieldValuesModel
     */
    protected $model;

    public function execute()
    {
        $field = $this->getField();
        if (!$field) {
            throw new waException(_ws("Unknown field"));
        }

        $parent_field = $this->getParentField();
        if (!$parent_field) {
            throw new waException(_ws("Unknown parent field"));
        }

        $ids = waRequest::post('delete', array(), waRequest::TYPE_ARRAY_INT);
        if (!empty($ids)) {
            $this->delete($ids);
        }

        $data = $this->getData($field, $parent_field);
        if (!empty($data)) {
            $this->save($data);
        }
    }

    public function delete(array $ids)
    {
        return $this->getModel()->deleteById($ids);
    }

    public function save(array $data)
    {
        return $this->getModel()->save($data);
    }

    /**
     * @return waContactFieldValuesModel
     */
    public function getModel()
    {
        if (!$this->model) {
            $this->model = new waContactFieldValuesModel();
        }
        return $this->model;
    }

    public function getData($field, $parent_field)
    {
        $update = array();
        $add = array();
        $values = waRequest::post('value', array());
        $parent_values = waRequest::post('parent_value', array());
        foreach (waRequest::post('parent', array()) as $parent_index) {
            if (empty($values[$parent_index])) {
                continue;
            }
            $sort = 0;
            foreach ($values[$parent_index] as $id => $value) {
                $id = (int)$id;
                $value = trim($value);
                if ($id > 0) {
                    $p = &$update[$id];
                } else {
                    if (!$value) {
                        continue;
                    }
                    $p = &$add[];
                }
                $p = array(
                    'parent_field' => $parent_field,
                    'parent_value' => $parent_values[$parent_index],
                    'field'        => $field,
                    'value'        => $value,
                    'sort'         => $sort
                );
                $sort += 1;
            }
        }
        return array('add' => $add, 'update' => $update);
    }

    public function getField()
    {
        return waRequest::get('field', null, waRequest::TYPE_STRING_TRIM);
    }

    public function getParentField()
    {
        $parent = waRequest::request('parent_field');
        if (!$parent) {
            return null;
        }
        $field_ids = explode(':', $parent);
        $f = waContactFields::get($field_ids[0]);
        if (!$f) {
            return null;
        }
        if ($f instanceof waContactCompositeField) {
            if (empty($field_ids[1])) {
                return null;
            }
            $subfields = $f->getFields();
            if (empty($subfields[$field_ids[1]])) {
                return null;
            }
            return $field_ids[0].':'.$field_ids[1];
        }
        return $field_ids[0];
    }
}