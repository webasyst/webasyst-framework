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

        if (!empty($result['header'])) {
            foreach ((array) $result['header'] as $name => $value) {
                $this->getResponse()->addHeader($name, $value);
            }
        }
        $this->view->assign('params', $params);
        $this->view->assign('result', $result);
    }
}
