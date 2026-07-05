<?php
/**
 * Create arhive with application, plugin, widget or theme.
 */
class developerBackendCompressAction extends developerAction
{
    private function compress()
    {
        $params = waRequest::post();
        if ($params['product'] === 'systemwidget') {
            $params['app_id'] = 'webasyst';
            $params[0] = 'wa-widgets';
        } elseif ($params['product'] === 'systemplugin') {
            $params['app_id'] = 'webasyst';
            $params[0] = 'wa-plugins/'.$params['type'];
        } elseif ($params['product'] === 'plugin') {
            $params[0] = $params['app_id'].'/plugins';
        } elseif ($params['product'] === 'theme') {
            $params[0] = $params['app_id'].'/themes';
        } elseif ($params['product'] === 'widget') {
            $params[0] = $params['app_id'].'/widgets';
        } else {
            $params[0] = '';
        }
        $params[0] .= '/'.$params['id'];
        $params['skip'] = 'test';
        $params['style'] = 'true';
        $params['php'] = PHP_BINARY;
        waRequest::setParam($params);

        ob_start();
        try {
            wao(new webasystCompressCli())->run();
            $response = ['status' => 'ok', 'data' => ob_get_clean()];
        } catch (Throwable $e) {
            ob_end_clean();
            $response = ['status' => 'fail', 'error' => $e->getMessage(), 'data' => null];
        }

        $this->getResponse()
             ->addHeader('Content-Type', 'application/json')
             ->sendHeaders();
        echo json_encode($response);
    }

    public function execute()
    {
        if (waRequest::isXMLHttpRequest()) {
            $this->compress();
            die();
        } else {
            $this->layout->assign('page', 'compress');
            $this->view->assign('apps', $this->getUser()->getApps());
        }
    }
}
