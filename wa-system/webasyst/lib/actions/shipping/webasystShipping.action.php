<?php

/**
 * Payments callback
 *
 * Available by URL:
 * @link http://ROOT_PATH/shipping/i:module_id/
 */
class webasystShippingAction extends waViewAction
{
    public function execute()
    {
        $params = waRequest::request();
        $params['result'] = true;
        $module_id = waRequest::param('module_id');

        $result = waShipping::execCallback($params, $module_id);

        if (!empty($result['template'])) {
            $this->template = $result['template'];
        }

        $this->view->assign('params', $params);
        $this->view->assign('result', $result);
    }
}
