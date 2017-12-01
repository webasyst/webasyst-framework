<?php

/**
 * Payments callback
 *
 * Available by URL:
 *     http://ROOT_PATH/payments.php/s:module_id/
 */
class webasystPaymentsAction extends waViewAction
{
    public function execute()
    {
        $params = waRequest::request();

        $result = waPayment::callback(waRequest::param('module_id'), $params);

        if (!empty($result['redirect'])) {
            $this->getResponse()->redirect($result['redirect']);
        }

        if (!empty($result['template'])) {
            $this->template = $result['template'];
        } elseif (isset($result['template'])) {
            exit;
        }

        wa()->setActive('webasyst');

        $response = $this->getResponse();
        if (!empty($result['code'])) {
            $response->setStatus($result['code']);
        }
        if (!empty($result['header'])) {
            foreach ((array)$result['header'] as $name => $value) {
                $response->addHeader($name, $value);
            }
        }

        $response->sendHeaders();
        $this->view->assign('params', $params);
        $this->view->assign('result', $result);
    }
}
