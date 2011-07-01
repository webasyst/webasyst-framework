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
		$params = $this->getRequest()->post();
		$params['result'] = true;
		$module_id = $this->getRequest()->param('module_id');
		
		$result = waPayment::execTransactionCallback($params, $module_id);

		$this->view->assign('params', $params);
		$this->view->assign('result', $result);		
	}
}