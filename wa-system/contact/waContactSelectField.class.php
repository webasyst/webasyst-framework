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
class waContactSelectField extends waContactField
{
    /**
     * Options for this select. array(id => name). Ids are stored in DB, and names are shown to user.
     * Default implementation uses 'options' parameter passed to constructor in $options
     * Could be redefined in subclasses to implement custom option list (e.g. from database).
     * @param string $id - id to return name for.
     * @throws waException
     * @return string|array - if $id is specified returns string with the name for this id. Id $id is null returns array(id => name) with all available options.
     */
    public function getOptions($id = null)
    {
        if (!isset($this->options['options']) || !is_array($this->options['options'])) {
            throw new waException('No options for waContactSelectField');
        }

        if ($id !== null) {
            if (!isset($this->options['options'][$id])) {
                throw new waException('Unknown id: '.$id);
            }

            if (empty($this->options['translate_options'])) {
                return $this->options['options'][$id];
            }
            return _ws($this->options['options'][$id]);
        }

        if (empty($this->options['translate_options'])) {
            return $this->options['options'];
        }

        $options = $this->options['options'];
        foreach($options as &$o) {
            $o = _ws($o);
        }
        return $options;
    }

    public function getInfo()
    {
        $data = parent::getInfo();
        $data['options'] = $this->getOptions();

        // In JS we cannot rely on the order of object properties during iteration
        // so we pass an order of keys as an array
        $data['oOrder'] = array_keys($data['options']);
        $data['defaultOption'] = _ws($this->getParameter('defaultOption'));
        return $data;
    }

    /**
     * Return 'Select' type, unless redefined in subclasses
     * @return string
     */
    public function getType()
    {
        return 'Select';
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        $value = isset($params['value']) ? $params['value'] : '';
        $html = '<select '.$attrs.' name="'.$this->getHTMLName($params).'"><option value=""></option>';
        foreach ($this->getOptions() as $k => $v) {
            $html .= '<option'.($k == $value ? ' selected="selected"' : '').' value="'.$k.'">'.htmlspecialchars($v).'</option>';
        }
        $html .= '</select>';
        return $html;
    }

    public function validate($data, $contact_id=null)
    {
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = array();
        }

        if ($this->getParameter('required') && !$this->options['validators']) {
            $this->options['validators'][] = new waStringValidator($this->options, array(
                'required' => _ws('Select value'),
            ));
        }

        return parent::validate($data, $contact_id);
    }

    public function getFormatter($format)
    {
        if ($format == 'html') {
            return new waContactSelectFormatter($this->getOptions());
        }
        return parent::getFormatter($format);
    }
}


class waContactSelectFormatter  extends waContactFieldFormatter
{
    public function format($data)
    {
        return isset($this->options[$data]) ? $this->options[$data]: $data;
    }
}
