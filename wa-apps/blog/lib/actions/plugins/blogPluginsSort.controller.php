<?php
/**
 * @author Webasyst
 *
 */
class blogPluginsSortController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->isAdmin($this->getApp())) {
            throw new waRightsException(_w('Access denied'));
        }

        try {
            $this->getConfig()->setPluginSort(waRequest::post('slug'), waRequest::post('pos', 0, 'int'));
            $this->response = 'ok';
        } catch (waException $e) {

            $this->setError($e->getMessage());
        }

        $this->getResponse()->addHeader('Content-type', 'application/json');
    }
}
