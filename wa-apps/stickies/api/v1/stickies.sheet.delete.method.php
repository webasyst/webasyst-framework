<?php
/**
 *
 * @author Webasyst
 *
 */
class stickiesSheetDeleteMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $id = (int)$this->post('id', true);

        $sheet_model = new stickiesSheetModel();
        if ($sheet_model->available($id)) {
            if ($sheet_model->deleteById($id)) {
                $this->response = true;
            } else {
                throw new waAPIException('server_error', 500);
            }
        } else {
            throw new waAPIException('access_denied', "Not enough rights to work with current board", 403);
        }
    }
}
