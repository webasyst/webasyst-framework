<?php
/**
 *
 * @author Webasyst
 *
 */
class stickiesStickyUpdateMethod extends stickiesAPIMethod
{
    protected $method = 'POST';
    public function execute()
    {
        $id = $this->get('id', true);
        $this->getSticky($id);

        $data = waRequest::post();
        if (isset($data['id'])) {
            unset($data['id']);
        }
        if (isset($data['sheet_id'])) {
            $this->checkRights($data['sheet_id']);
        }

        $sticky_model = new stickiesStickyModel();
        if ($sticky_model->modify($id, $data)) {
            $method = new stickiesSheetGetInfoMethod();
            $this->response = $method->getResponse(true);
        } else {
            throw new waAPIException('server_error', 500);
        }
    }
}
