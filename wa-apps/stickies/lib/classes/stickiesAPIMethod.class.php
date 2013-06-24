<?php

abstract class stickiesAPIMethod extends waAPIMethod
{
    protected function checkRights($sheet_id)
    {
        $sheet_model = new stickiesSheetModel();
        $sheet = $sheet_model->getById($sheet_id);

        if (!$sheet) {
            throw new waAPIException('invalid_param', 'Sheet not found', 404);
        }

        if (!$this->getRights('sheet.'.$sheet_id)) {
            throw new waAPIException('access_denied', "Not enough rights to work with current board", 403);
        }
        return true;
    }

    protected function getSticky($id)
    {
        $sticky_model = new stickiesStickyModel();
        $sticky = $sticky_model->getById($id);
        if (!$sticky) {
            throw new waAPIException('invalid_param', 'Sticky not found', 404);
        }
        $this->checkRights($sticky['sheet_id']);
        return $sticky;
    }
}
