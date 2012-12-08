<?php 

/**
 * Payments callback
 * 
 * Available by URL:
 *     http://ROOT_PATH/payments/i:module_id/
 */
class webasystPaymentsAction extends waViewAction
{
    public function execute()
    {
        $params = waRequest::request();
        $params['result'] = true;
        $module_id = waRequest::param('module_id');

        $result = waPayment::execTransactionCallback($params, $module_id);

        if (!empty($result['template'])) {
            $this->template = $result['template'];
        }

        $this->view->assign('params', $params);
        $this->view->assign('result', $result);
    }
}