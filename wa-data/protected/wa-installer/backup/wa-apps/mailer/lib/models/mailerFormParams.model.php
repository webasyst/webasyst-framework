<?php
/**
 * Model for form params
 */

class mailerFormParamsModel extends waModel
{
    protected $table = 'mailer_form_params';

    /**
     * Get custom params of form
     * @param array|int $ids form ID
     */
    public function get($ids)
    {
        if (!$ids) {
            return array();
        }
        $params = array();
        foreach ($this->getByField('form_id', $ids, true) as $p) {
            $params[$p['form_id']][$p['name']] = $p['value'];
            if ($p['name'] == 'fields') {
                $params[$p['form_id']][$p['name']] = unserialize($p['value']);
            }
        }
        if (is_numeric($ids)) {
            $params = isset($params[$ids]) ? $params[$ids] : array();
        } else {
            foreach ($ids as $id) {
                if (!isset($params[$id])) {
                    $params[$id] = array();
                }
            }
        }
        return $params;
    }
    /**
     * Get value of one custom param on form
     * @param int $form_id
     * @param string $name
     */
    public function getOne($form_id, $name)
    {
        $item = $this->getByField(array(
            'form_id' => $form_id,
            'name' => $name
        ));
        return $item ? $item['value'] : null;
    }

    /**
     *
     * @param int $form_id
     * @param string $name
     * @param string $value
     */
    public function setOne($form_id, $name, $value)
    {
        return $this->set($form_id, array($name => $value), false);
    }

    /**
     * Set custom params to form
     *
     * @param int|array $id Form ID
     * @param array|null $params key=>value format of array or null (to delete all params assigned to form)
     * @param bool $delete_old
     * @return bool
     */
    public function set($id, $params = array(), $delete_old = true)
    {
        if ($id) {
            if (!is_null($params) && array_key_exists('fields', $params)) {
                $params['fields'] = serialize($params['fields']);
            }

            $id = (array)$id;

            // remove if params is null
            if (is_null($params)) {
                return $this->deleteByField(array(
                    'form_id' => $id
                ));
            }

            if (empty($params)) {
                return true;
            }

            // old params (candidate to delete)
            $old_params = $this->get($id);

            // accumulate params to add (new params) and update old params
            $add_params = array();
            foreach ($params as $name => $value) {
                foreach ($id as $form_id) {
                    if (isset($old_params[$form_id][$name])) {
                        if ($value === null) {
                            // delete this param
                            $this->deleteByField(array(
                                'form_id' => $id,
                                'name' => $name
                            ));
                        } else {
                            // update old param
                            $this->updateByField(array('form_id' => $id, 'name' => $name), array('value' => $value));
                        }
                        // remove from candidate to delete
                        unset($old_params[$form_id][$name]);
                    } else {
                        // param to add
                        $add_params[] = array(
                            'form_id' => $form_id,
                            'name' => $name,
                            'value' => $value
                        );
                    }
                }
            }

            if ($delete_old) {
                // delete
                foreach ($old_params as $prms) {
                    foreach ($prms as $name => $value) {
                        $this->deleteByField(array(
                            'form_id' => $id,
                            'name' => $name
                        ));
                    }
                }
            }

            // add new params
            if ($add_params) {
                $this->multipleInsert($add_params);
            }

            return true;
        }
        return false;
    }
}