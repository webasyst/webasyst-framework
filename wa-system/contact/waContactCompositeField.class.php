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
 * @subpackage contact
 */
class waContactCompositeField extends waContactField
{
    protected function init()
    {
        if (!isset($this->options['required'])) {
            $this->options['required'] = array();
        }

        $this->prepareSubfields();
    }

    public function getInfo()
    {
        $info = parent::getInfo();
        foreach ($this->options['fields'] as $field) {
            /**
             * @var $field waContactField
             */
            $info['fields'][$field->getId()] = $field->getInfo();
        }
        return $info;
    }

    public function get(waContact $contact, $format = null)
    {
        $data = $this->getStorage()->get($contact, $this->getId());
        if ($data) {
            if ($this->isMulti()) {
                foreach ($data as &$row) {
                    $row = $this->format($row, $format);
                }
                return $data;
            } else {
                return $this->format($data, $format);
            }
        } else {
            return array();
        }
    }

    public function validate($data, $contact_id = null)
    {
        $errors = null;
        if (!$this->isMulti()) {
            $data = array($data);
        }
        if (is_array($data)) {
            foreach ($data as $sort => $value) {
                if (isset($value['data'])) {
                    $v = &$value['data'];
                } elseif (isset($value['value'])) {
                    $v = &$value['value'];
                } else {
                    $v = &$value;
                }
                foreach ($this->options['fields'] as $field) {
                    /**
                     * @var waContactField $field
                     */
                    $subId = $field->getId();
                    $str = isset($v[$subId]) ? trim($v[$subId]) : '';
                    if ($str) {
                        if ( ( $e = $field->validate($v[$subId]))) {
                            $errors[$sort][$subId] = $e;
                        }
                    } else if ($field->isRequired() || (isset($this->options['required']) && in_array($subId, $this->options['required']))) {
                        $errors[$sort][$subId] = sprintf(_ws('%s subfield is required.'), $field->getName());
                    }
                }
            }
        } else if ($data !== null) {
            return array(_ws('Data must be an array.'));
        }

        if (!$this->isMulti() && $errors) {
            return $errors[0];
        }
        return $errors;
    }

    public function format($data, $format = null)
    {
        if (!isset($data['value'])) {
            $value = array();
            foreach ($this->options['fields'] as $field) {
                if (isset($data['data'][$field->getId()])) {
                    $value[] = htmlspecialchars($field->getName()).": ".$field->format($data['data'][$field->getId()], 'value', $data['data']);
                }
            }
            $data['value'] = implode("<br>\n", $value);
        }

        $found = true;

        if (strpos($format, ',')) {
            // when formats are delimeted by comma, use the first one that exists
            $found = false;
            foreach(explode(',', $format) as $format) {
                if ($format == 'value' || $format == 'html' || $this->getFormatter($format)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return $data;
            }
        }

        if ($format == 'html') {
            // Override logic for this format to avoid double htmlspecialchars()
            $result = $data['value'];
            if ($this->isMulti() && !empty($data['ext'])) {
                $ext = $data['ext'];
                if (isset($this->options['ext'][$ext])) {
                    $ext = _ws($this->options['ext'][$ext]);
                }
                $result .= ' <em class="hint">'.htmlspecialchars($ext).'</em>';
            }
            return $result;
        } else {
            return parent::format($data, $format);
        }
    }

    /**
     * @param bool $with_parent_id
     * @return array
     */
    public function getField($with_parent_id = true)
    {
        $result = array();
        foreach ($this->options['fields'] as $field) {
            /**
             * @var waContactField $field
             */
            $result[] = ($with_parent_id ? $this->getId().":" : "").$field->getId();
        }
        return $result;
    }

    public function set(waContact $contact, $value, $params = array(), $add = false)
    {
        $subfield = isset($params['subfield']) ? $params['subfield'] : '';
        if ($this->isMulti()) {
            $is_ext = $this->isExt();
            $ext = isset($params['ext']) ? $params['ext'] : '';

            if ($subfield) {
                if ($add) {
                    $values = $contact->get($this->getId());
                    if (($n = count($values)) > 0) {
                        $data = $values[$n - 1];
                        $data_ext = isset($data['ext']) ? $data['ext'] : null;
                        if (isset($data['fill']) && !isset($data['data'][$subfield]) && $ext == $data_ext) {
                            $values[$n - 1]['data'][$subfield] = $value;
                            return $values;
                        }
                    }
                    $values[] = array(
                        'data' => array(
                            $subfield => $value
                        ),
                        'fill' => true,
                        'ext' => $ext
                    );
                    return $values;
                } else {
                    if ($is_ext && $ext) {
                        $data = $contact->get($this->id);
                        foreach ($data as $sort => &$row) {
                            if ($row['ext'] == $ext) {
                                $row['data'][$subfield] = $value;
                            }
                        }
                        unset($row);
                        return $data;
                    } else {
                        // !!! this does not seem right, honestly...
                        return array(
                            array(
                                'data' => array(
                                    $subfield => $value
                                ),
                                'ext' => $ext
                            )
                        );
                    }
                }
            }

            if (isset($value[0])) {
                foreach ($value as &$v) {
                    $v = $this->setValue($v);
                    if ($is_ext && $ext) {
                        $v['ext'] = $ext;
                    }
                }
                unset($v);
            } else {
                $value = $this->setValue($value);
                if ($is_ext && $ext) {
                    $value['ext'] = $ext;
                }
                $value = array($value);
            }
            if ($add) {
                $data = $contact->get($this->id);
                foreach ($value as $v) {
                    $data[] = $v;
                }
                return $data;
            } else {
                if ($is_ext && $ext) {
                    $data = $contact->get($this->id);
                    foreach ($data as $sort => $row) {
                        if ($row['ext'] == $ext) {
                            unset($data[$sort]);
                        }
                    }
                    foreach ($value as $v) {
                        $data[] = $v;
                    }
                    return $data;
                } else {
                    return $value;
                }
            }
        } else {
            if ($subfield) {
                $data = $contact->get($this->getId());
                $data['data'][$subfield] = $value;
                return $data;
            }
            return $this->setValue($value);
        }
    }

    protected function setValue($value)
    {
        if (!isset($value['value']) && !isset($value['data'])) {
            foreach ($this->getFields() as $sf) {
                $sf_id = $sf->getId();
                if (isset($value[$sf_id])) {
                    $value['data'][$sf_id] = $value[$sf_id];
                    unset($value[$sf_id]);
                }
            }
        } elseif (isset($value['value']) && !isset($value['data'])) {
            $value['data'] = $value['value'];
            unset($value['value']);
        }
        return $value;
    }

    /**
     * @param string $subfield_name
     * @return array|waContactField
     */
    public function getFields($subfield_name = null)
    {
        $fields = array();
        foreach($this->options['fields'] as $f) {
            if ($f->getId() === $subfield_name) {
                return $f;
            }
            $fields[$f->getId()] = $f;
        }
        if ($subfield_name !== null) {
            return null;
        }
        return $fields;
    }

    public function prepareVarExport()
    {
        foreach ($this->options['fields'] as $f) {
            $f->prepareVarExport();
        }
    }

    public function setParameter($p, $value)
    {
        if ($p === 'required') {
            if (!$value || !is_array($value)) {
                $value = array();
            }
        }

        parent::setParameter($p, $value);

        if ($p === 'fields') {
            $this->prepareSubfields();
        }
    }

    protected function prepareSubfields()
    {
        // Being paranoid: ensure options[fields] does not contain any garbage
        if (empty($this->options['fields']) || !is_array($this->options['fields'])) {
            $this->options['fields'] = array();
        }
        foreach($this->options['fields'] as $subfield_id => $subfield) {
            if (!$subfield instanceof waContactField) {
                waLog::log('Bad configuration for '.$this->getId().'.'.$subfield_id.' contact field: not an instance of waContactField. Subfield is ignored.');
                unset($this->options['fields'][$subfield_id]);
            } else if ($subfield_id != $subfield->getId()) {
                unset($this->options['fields'][$subfield_id]);
                $this->options['fields'][$subfield->getId()] = $subfield;
            }
        }
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        $result = array();
        $params_subfield = $params;
        $value = ifset($params['value'], array());
        $data = ifset($value['data'], array());
        $params_subfield['composite_value'] = $data;

        if (!isset($params['id'])) {
            $params['id'] = $this->getId();
        }

        if (wa()->getEnv() == 'backend') {
            $required_class = 'required ';
        } else {
            $required_class = 'wa-required ';
        }

        foreach ($this->options['fields'] as $field) {
            $params_subfield['id'] = $field->getId();
            $params_subfield['parent'] = $params['id'];
            $params_subfield['value'] = ifset($data[$field->getId()]);

            if (!strlen($params_subfield['value'])) {
                $default_value = $field->getParameter('value');
                if ($default_value) {
                    $params_subfield['value'] = $default_value;
                }
            }

            $errors_html = '';
            $attrs_one = $attrs;
            if (!empty($params['validation_errors']) && !empty($params['validation_errors'][$field->getId()])) {
                $params_subfield['validation_errors'] = $params['validation_errors'][$field->getId()];
                $attrs_one = preg_replace('~class="~', 'class="error ', $attrs_one);
                if (false === strpos($attrs_one, 'class="error')) {
                    $attrs_one .= ' class="error"';
                }
            } else {
                unset($params_subfield['validation_errors']);
            }

            if ($field instanceof waContactHiddenField) {
                $result[] = $field->getHTML($params_subfield, $attrs_one);
            } else {
                $field_class = 'field-'.$this->getId().'-'.$field->getId();
                if (wa()->getEnv() == 'frontend') {
                    $field_class = 'wa-'.$field_class;
                }
                $result[] = '<span class="'.($field->isRequired() ? $required_class : '').'field '.$field_class.'"><span>'.$field->getName().'</span>'.$field->getHTML($params_subfield, $attrs_one).$errors_html.'</span>';
            }
        }
        return implode($result);
    }

    public function getHtmlOneWithErrors($errors, $params = array(), $attrs = '')
    {
        $params['validation_errors'] = $errors;
        return $this->getHtmlOne($params, $attrs);
    }
}

// EOF