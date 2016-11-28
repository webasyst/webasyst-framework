<?php

/**
 * Shipping callback
 *
 * Available by URL:
 * @link http://ROOT_PATH/shipping.php/s:module_id/
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
