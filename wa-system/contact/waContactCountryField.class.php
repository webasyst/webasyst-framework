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
class waContactCountryField extends waContactSelectField
{
    /**
     * @var waCountryModel
     */
    protected $model = null;
    
    public function init()
    {
        if (!isset($this->options['formats']['value'])) {
            $this->options['formats']['value'] = new waContactCountryFormatter();
        }
    }
    
    public function getOptions($id = null) 
    {
        if (!$this->model) {
            $this->model = new waCountryModel();
        }
        if ($id) {
            if (! ( $result = $this->model->name($id))) {
                throw new Exception('Unknown country ISO-3 code: '.$id);
            }
            return $result;
        }
        
        $result = $this->model->all();
        foreach($result as &$row) {
            $row = $row['name'];
        }
        return $result;
    }
    
    public function getType()
    {
        return 'Country';
    }

    public function getHTML($params = array(), $attrs = '')
    {
        $url = wa()->getRootUrl().'wa-content/img/country/';
        $id = 'wa-country-field-'.uniqid();
        $html = parent::getHTML($params, $attrs.' id="'.$id.'"');
        $html = '<i style="display:none" class="icon16" style=""></i>'.$html;
        $html .= '<script>
if ($) {
    $("#'.$id.'").change(function () {
        if ($(this).val()) {
            $(this).prev().show().css("background", "url('.$url.'" + $(this).val() + ".gif) 0 center no-repeat");
        } else {
            $(this).prev().hide();
        }
    }).change();
}
</script>';
        return $html;
    }

}

class waContactCountryFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        return waCountryModel::getInstance()->name($data);
    }
}

// EOF