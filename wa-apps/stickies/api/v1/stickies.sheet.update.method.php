<?php
/**
 *
 * @author Webasyst
 *
 */
class stickiesSheetUpdateMethod extends waAPIMethod
{

    protected $method = 'POST';
    public function execute($params)
    {
        $id = $this->get('id', true);
        $sheet_model = new stickiesSheetModel();

        $data = array();
        if (waRequest::post('name') !== null) {
            $data['name'] = waRequest::post('name');
        }
        if (waRequest::post('background_id') !== null) {
            $data['background_id'] = waRequest::post('background_id');
        }

        if (!$data) {
            throw new waAPIException('invalid_param', 'Nothing to update');
        }

        if ($sheet_model->available($id)) {
            if ($sheet_model->updateById($id, $data)) {
                $method = new stickiesSheetGetInfoMethod();
                $this->response = $method->getResponse(true);
            } else {
                throw new waAPIException('server_error', 500);
            }
        } else {
            throw new waAPIException('access_denied', "Not enough rights to work with current board", 403);
        }
    }
}
