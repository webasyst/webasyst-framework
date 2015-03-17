<?php 

/**
 * @param $params
 * @param $smarty
 * @return string|void
 */
function smarty_function_wa_action($params, &$smarty)
{
    $current_app = waSystem::getInstance()->getApp();

    $app = $params['app'];
    waSystem::getInstance($app)->setActive($app);

    if (isset($params['action'])) {
        $type = 'action';
        $class_name = $app.ucfirst($params['module']).ucfirst($params['action']).'Action';
    } elseif (isset($params['controller'])) {
        $type = 'controller';
        $class_name = $app.ucfirst($params['module']).ucfirst($params['controller']).'Controller';
    }

    $var = isset($params['var'])?$params['var']:false;
    if($var){
        unset($params['var']);
    }
    unset($params['app']);
    unset($params['module']);
    unset($params['action']);
    foreach ($params as $key => $value) {
        waRequest::setParam($key, $value);
    }
    $result = '';
    try {
        if ($type == 'action') {
            $action = new $class_name();
            $result = $action->display();
        } elseif ($type == 'controller') {
            $controller = new $class_name();
            $result = $controller->execute();
        }
    } catch (Exception $e) {
        $result = $e->getMessage();
    }
    waSystem::setActive($current_app);
    if ($var) {
        $smarty->assign($var,$result);
    } else{
        return $result;
    }
}