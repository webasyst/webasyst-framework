<?php
/**
 * 
 * @author Webasyst
 *
 */
class stickiesSheetGetListMethod extends waAPIMethod
{
    protected $method = 'GET';
    public function execute()
    {
        $fields = waRequest::get('fields', 'name', 'string');
        if (!$fields) {
            $fields = 'name';
        }
        $sheet_model = new stickiesSheetModel();
        $this->response = $sheet_model->get(false, $fields);
        $this->response['_element'] = 'sheet';
    }
}

