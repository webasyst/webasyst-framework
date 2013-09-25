<?php

class siteRoutingEditAction extends waViewAction
{

    public function execute()
    {
        $route_id = waRequest::get('route', '');
        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());
        if ($route_id && !isset($routes[$route_id])) {
            throw new waException('Route not found', 404);
        }

        if ($route_id || strlen($route_id)) {
            $route = $routes[$route_id];
            $app_id = ifset($route['app']);
        } else {
            $route = array();
            $apps = wa()->getApps();
            foreach ($apps as $app_id => $app) {
                if (empty($app['frontend'])) {
                    unset($apps[$app_id]);
                }
            }
            reset($apps);
            $this->view->assign('apps', $apps);
            $app_id = waRequest::get('app', key($apps));
        }

        if ($app_id) {
            $path = $this->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
            $app = wa()->getAppInfo($app_id);
            if (file_exists($path)) {
                // load locale of the app
                if ($app_id != 'site') {
                    waSystem::getInstance($app_id)->setActive($app_id);
                }
                $app['site'] = include($path);
                // return old locale of the site
                if ($app_id != 'site') {
                    waSystem::setActive('site');
                }
            }

            if (!$route && isset($app['routing_params']) && is_array($app['routing_params'])) {
                $route = $app['routing_params'];
            }

            if (isset($app['site']['params'])) {
                $params = $this->getParams($route_id, $app['site']['params'], $route);
            } else {
                $params = array();
            }

            if (!isset($route['_name'])) {
                if ($app_id == 'site') {
                    if ($title = siteHelper::getDomain('title')) {
                        $route_name = $title;
                    } else {
                        $app_settings_model = new waAppSettingsModel();
                        $route_name = $app_settings_model->get('webasyst', 'name', 'Webasyst');
                    }
                } else {
                    $route_name = $app['name'];
                }
            } else {
                $route_name = $route['_name'];
            }

            $this->view->assign('route_name', $route_name);
            $this->view->assign('params', $params);

        } else {
            $app = array();
        }
        $this->view->assign('route_id', $route_id);
        $this->view->assign('route', $route);
        $this->view->assign('app_id', $app_id);
        $this->view->assign('app', $app);
        $this->view->assign('domain_id', siteHelper::getDomainId());
        $this->view->assign('domain', siteHelper::getDomain());
        $this->view->assign('locales', array('' => _w('Auto')) + waLocale::getAll('name'));
    }

    protected function getParams($route_id, $config, $values)
    {
        $result = array();
        foreach ($config as $id => $info) {
            $info['id'] = $id;
            $result[$id] = array(
                'name' => $info['name'],
                'value' => $this->getHTML($route_id, $info, isset($values[$id]) ? $values[$id]: null)
            );
        }
        return $result;
    }

    protected function getHTML($route_id, $info, $value)
    {
        static $id = 0;
        $html = '';
        if ( ($value === null) && isset($info['default']) ) {
            $value = $info['default'];
        }
        switch ($info['type']) {
            case 'input':
                $html = '<input type="text" name="params['.$info['id'].']" value="'.htmlspecialchars($value).'">';
                return $html;
            case 'textarea':
                $html = '<textarea name="params['.$info['id'].']">'.htmlspecialchars($value).'</textarea>';
                return $html;
            case 'select':
                $html = '<select name="params['.$info['id'].']">';
                foreach ($info['items'] as $k => $v) {
                    if(!is_array($v)) {
                        $v = array('name'=>$v);
                    }
                    $html .= '<option '.($k == $value ? 'selected="selected" ' : '').'value="'.$k.'"'.
                                (isset($v['description'])?(' title="'.htmlspecialchars($v['description']).'"'):'').'">'.htmlspecialchars($v['name']).
                            '</option>';
                }
                $html .= '</select>';
                if (isset($info['description'])) {
                    $html .= '<p class="hint">'.$info['description'].'</p>';
                }
                return $html;
            case 'radio':
                foreach ($info['items'] as $k => $v) {
                    if(!is_array($v)) {
                        $v = array('name'=>$v);
                    }
                    $html .= '<label class="s-label-with-check">'.
                                 '<input type="radio" name="params['.$info['id'].']" value="'.$k.'" />'.htmlspecialchars($v['name']).
                    (isset($v['description'])?(' <span class="hint">'.$v['description'].'</span>'):'').
                             '</label>';
                }
                return $html;
            case 'checkbox':
                foreach ($info['items'] as $k => $v) {
                    if(!is_array($v)) {
                        $v = array('name'=>$v);
                    }
                    $html .= '<label class="s-label-with-check">'.
                                '<input type="checkbox" name="params['.$info['id'].'][]" value="'.$k.'" />'.htmlspecialchars($v['name']).
                    (isset($v['description'])?(' <span class="hint">'.$v['description'].'</span>'):'').
                                '</label>';
                }
                return $html;
            case 'radio_select':
                $html = '<div id="s-radio-select-'.$route_id.'-'.++$id.'">';
                foreach ($info['items'] as $k => $v) {
                    if(!is_array($v)) {
                        $v = array('name'=>$v);
                    }
                    $html .= '<label class="s-label-with-check">'.
                                 '<input type="radio" '.
                    ($value == $k || isset($v['items'][$value]) ? 'checked="checked"' : '').
                                    ' name="params['.$info['id'].']" value="'.$k.'" />'.
                    htmlspecialchars($v['name']).(isset($v['description'])?(' <span class="hint">'.$v['description'].'</span>'):'').
                             '</label>';
                    if (isset($v['items'])) {
                        $html .= '<select '.($value == $k || isset($v['items'][$value]) ? '' : 'disabled="disabled"').' name="params['.$info['id'].']">';
                        foreach ($v['items'] as $k2 => $v2) {
                            $html .= '<option '.($k2 == $value ? 'selected="selected" ' : '').'value="'.$k2.'">'.htmlspecialchars($v2).'</option>';
                        }
                        $html .= '</select>';
                    }
                }
                $html .= '</div>';
                $html .= '<script type="text/javascript">
                $("#s-radio-select-'.$route_id.'-'.$id.' input[type=radio]").change(function () {
                    if ($(this).is(":checked")) {
                        $("#s-radio-select-'.$route_id.'-'.$id.' select").attr("disabled", "disabled");
                        $(this).parent().next("select").removeAttr("disabled");
                    }
                });
                </script>';
                return $html;

            case 'radio_checkbox':
                $html = '<div id="s-radio-checkbox-'.$route_id.'-'.++$id.'">';
                foreach ($info['items'] as $k => $v) {
                    if(!is_array($v)) {
                        $v = array('name'=>$v);
                    }
                    $html .= '<label class="s-label-with-check">'.
                        '<input type="radio" '.
                        ($value == $k || (is_array($value) && isset($v['items'])) ? 'checked="checked"' : '').
                        ' name="params['.$info['id'].']" value="'.$k.'" />'.
                        htmlspecialchars($v['name']).(isset($v['description'])?(' <span class="hint">'.$v['description'].'</span>'):'').
                        '</label>';
                    if (isset($v['items'])) {
                        $disabled = !is_array($value);
                        $html .= '<div class="block"><ul class="menu-v compact small">';
                        foreach ($v['items'] as $k2 => $v2) {
                            if (!is_array($v2)) {
                                $v2 = array('name' => $v2);
                            }
                            $html .= '<li><label class="s-label-with-check">'.
                                '<input '.(is_array($value) && in_array($k2, $value) ? 'checked' :'' ).' '.($disabled ? 'disabled="disabled"' : '').' type="checkbox" name="params['.$info['id'].'][]" value="'.$k2.'" />'.htmlspecialchars($v2['name']).
                                (isset($v2['description'])?(' <span class="hint">'.$v2['description'].'</span>'):'').
                                '</label></li>';
                        }
                        $html .= '</ul></div>';
                    }
                }
                $html .= '</div>';
                $html .= '<script type="text/javascript">
                $("#s-radio-checkbox-'.$route_id.'-'.$id.' input[type=radio]").change(function () {
                    if ($(this).is(":checked")) {
                        $("#s-radio-checkbox-'.$route_id.'-'.$id.' input:checkbox").attr("disabled", "disabled").removeAttr("checked");
                        $(this).parent().next("div").find("input").removeAttr("disabled");
                    }
                });
                </script>';
                return $html;

            case 'radio_text':
                $html = '<div id="s-radio-select-'.$route_id.'-'.++$id.'">';
                $counter = 0;
                $selected = false;
                foreach ($info['items'] as $k => $v) {
                    if(!is_array($v)) {
                        $v = array('name'=>$v);
                    }
                    $checked = (sprintf('%s',$k) === $value);
                    $last = (++$counter == count($info['items']));
                    if ($last) {
                        $checked = !$selected;
                    } elseif ($checked) {
                        $selected = true;
                    }
                    $html .= '<label class="s-label-with-check">'.
                                 '<input type="radio" '.
                    ($checked? 'checked="checked"' : '').
                                    ' name="params['.$info['id'].']" value="'.$k.'" '.($last?' class="last"':'').'/>'.
                    htmlspecialchars($v['name']).(isset($v['description'])?(' <span class="hint">'.$v['description'].'</span>'):'').
                             '</label>';
                    if ($last) {
                        $html .= '<input '.(($checked) ? '' : 'disabled="disabled"').' name="params['.$info['id'].']"'.($checked && ($value != $k)?' value="'.htmlentities($value,ENT_QUOTES,'utf-8').'"':'').'/>';
                    }
                }
                $html .= '</div>';
                $html .= '<script type="text/javascript">
                $("#s-radio-select-'.$route_id.'-'.$id.' input[type=radio]").change(function () {
                    if ($(this).hasClass("last")) {
                        $("#s-radio-select-'.$route_id.'-'.$id.' input:text").removeAttr("disabled").focus();
                    } else {
                        $("#s-radio-select-'.$route_id.'-'.$id.' input:text").attr("disabled", "disabled");
                    }
                });
                </script>';
                return $html;
            default:
                return '';
        }
    }
}