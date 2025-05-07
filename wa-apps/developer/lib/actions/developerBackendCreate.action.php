<?php
/**
 * Creates application/plugin/widget/theme.
 */
class developerBackendCreateAction extends developerAction
{
    protected function createProduct()
    {
        $params = waRequest::post();
        if ($params['product'] === 'systemwidget') {
            $params['product'] = 'widget';
            $params['app_id'] = 'webasyst';
        }
        $params[$params['product'].'_id'] = $params['id'];
        if ($params['product'] === 'systemplugin') {
            $params[0] = $params['type'];
        } elseif ($params['product'] === 'app') {
            $params[0] = $params['id'];
        } else {
            $params[0] = $params['app_id'];
        }
        if ($params['product'] !== 'app') {
            $params[1] = $params['id'];
        }
        waRequest::setParam($params);

        $class = 'developerCreate' . ucfirst($params['product']);
        if (!class_exists($class)) {
            $class = 'webasystCreate' . ucfirst($params['product']) . 'Cli';
        }

        try {
            ob_start();
            wao(new $class())->run();
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
            $this->createProduct();
            exit(1);
        }

        $this->layout->assign('page', 'create');
        $this->view->assign([
            'apps' => $this->getUser()->getApps(),
            'icons' => waFiles::listdir($this->getConfig()->getAppPath('img/icons')),
        ]);
    }
}
