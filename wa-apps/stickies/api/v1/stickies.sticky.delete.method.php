<?php
/**
 *
 * @author Webasyst
 *
 */
class stickiesStickyDeleteMethod extends stickiesAPIMethod
{
    protected $method = 'POST';
    public function execute()
    {
        $id = $this->post('id', true);
        $this->getSticky($id);

        $sticky_model = new stickiesStickyModel();
        if ($sticky_model->delete($id)) {
            $this->response = true;
        } else {
            throw new waAPIException('server_error', 500);
        }
    }
}
