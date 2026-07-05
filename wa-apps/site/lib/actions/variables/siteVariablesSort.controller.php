<?php

class siteVariablesSortController extends waJsonController {
    public function execute() {
        $this->response = (new siteVariableModel())->move(waRequest::post('id'), waRequest::post('pos', 1, 'int'));
    }
}
