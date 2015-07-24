<?php
/**
 * 
 * @author Webasyst
 *
 */
class stickiesSheetAddMethod extends waAPIMethod
{
    protected $method = 'POST';
    public function execute()
    {
        $name = $this->post('name', true);

        $sheet_model = new stickiesSheetModel();
        $sheet_id = $sheet_model->create($name, waRequest::post('background_id'));

        if ($sheet_id) {
            $_GET['id'] = $sheet_id;
            $method = new stickiesSheetGetInfoMethod();
            $this->response = $method->getResponse(true);
        } else {
            throw new waAPIException('server_error', 500);
        }
    }
}
