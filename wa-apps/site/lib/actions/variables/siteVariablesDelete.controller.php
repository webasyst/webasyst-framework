<?php

class siteVariablesDeleteController extends waJsonController {
    /**
     * @throws \waException
     */
    public function execute() {
        $id = waRequest::post('id');
        $model = new siteVariableModel();
        $variable = $model->getById($id);
        if ($variable) {
            $model->deleteById($id);
            $this->logAction('variable_delete');
        }
    }
}
