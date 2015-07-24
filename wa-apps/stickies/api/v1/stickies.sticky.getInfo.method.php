<?php
/**
 *
 * @author Webasyst
 *
 */
class stickiesStickyGetInfoMethod extends stickiesAPIMethod
{
    public function execute()
    {
        $id = (int)$this->get('id', true);
        $sticky_model = new stickiesStickyModel();
        $sticky = $sticky_model->getById($id);

        if (!$sticky) {
            throw new waAPIException('invalid_param', 'Sticky not found', 404);
        }

        $this->checkRights($sticky['sheet_id']);
        $this->response = $sticky;
    }
}
