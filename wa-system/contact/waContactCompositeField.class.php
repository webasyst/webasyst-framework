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
    }

    public function getInfo()
    {
        $info = parent::getInfo();
        foreach ($this->options['fields'] as $field) {
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
        foreach ($data as $sort => $value) {
            if (isset($value['data'])) {
                $v = &$value['data'];
            } elseif (isset($value['value'])) {
                $v = &$value['value'];
            } else {
                $v = &$value;
            }
            foreach ($this->options['fields'] as $field) {
                $subId = $field->getId();
                $str = isset($v[$subId]) ? trim($v[$subId]) : '';
                if ($str) {
                    if ( ( $e = $field->validate($v[$subId]))) {
                        $errors[$sort][$subId] = $e;
                    }
                } else if (isset($this->options['required']) && in_array($subId, $this->options['required'])) {
                    $errors[$sort][$subId] = sprintf(_ws('%s subfield is required.'), $field->getName());
                }
            }
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
                    $value[] = htmlspecialchars($field->getName().": ".$field->format($data['data'][$field->getId()], 'value'));
                }
            }
            $data['value'] = implode("<br>\n", $value);
        }
        return parent::format($data, $format);
    }

    public function getField($with_parent_id = true)
    {
        $result = array();
        foreach ($this->options['fields'] as $field) {
            $result[] = ($with_parent_id ? $this->getId().":" : "").$field->getId();
        }
        return $result;
    }
    
    public function set($value)
    {
		if ($this->isMulti()) {
			if (isset($value[0])) {
				foreach ($value as &$v) {
					$v = $this->setValue($v);
				}
				return $value;
			} else {
				return array(
					$this->setValue($value)
				);
			}
		} else {
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

    public function getFields()
    {
        return $this->options['fields'];
    }

    public function setParameter($p, $value) {
        if ($p === 'required') {
            if (!$value || !is_array($value)) {
                $value = array();
            }
        }
        parent::setParameter($p, $value);
    }
}

// EOF