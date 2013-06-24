<?php
/**
 * 
 * @author WebAsyst Team
 *
 */
class stickiesSheetGetInfoMethod extends stickiesApiMethod
{

    protected $method = 'GET';
    public function execute()
    {
        $id = $this->get('id', true);
        $sheet_model = new stickiesSheetModel();
        $this->checkRights($id);
        $this->response = $sheet_model->get($id);
    }
}
