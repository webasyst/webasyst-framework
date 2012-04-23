<?php

    function smarty_block_wa_js($params, $content, &$smarty) {
        if (!$content) {
            return '';
        }
        // jquery ui custom bundle
        $ui_custom = array(
            'core' => 0,
            'widget' => 0,
            'mouse' => 0,
            'draggable' => 0,
            'droppable' => 0,
            'sortable' => 0,
            'datepicker' => 1
        );
                
        $files = explode("\n", $content);
        
        $wa = waSystem::getInstance();

        $jquery_ui_path = "wa-content/js/jquery-ui/jquery.ui.";
        $jquery_ui_path_n = strlen($jquery_ui_path);

        $n = strlen($wa->getRootUrl());

        $locale = $wa->getLocale();
        
        if (!SystemConfig::isDebug() && isset($params['file'])) {
            $root_path = $wa->getConfig()->getRootPath();
            $app_path = $wa->getConfig()->getAppPath();
            $result = '';
            $files_combine = array();
            $mtime = file_exists($app_path.'/'.$params['file']) ? filemtime($app_path.'/'.$params['file']) : 0;
            $r = true;
            foreach ($files as $f) {
                $f = trim($f);
                $f = substr($f, $n);
                if ($f) {
                    if (substr($f, 0, $jquery_ui_path_n) == $jquery_ui_path) {
                        $jquery_f = substr($f, $jquery_ui_path_n);
                        if (substr($jquery_f, -7) == '.min.js') {
                            $jquery_f = substr($jquery_f, 0, -7);
                        }
                        if (isset($ui_custom[$jquery_f])) {
                            if (!$result) {
                                $result = '<script type="text/javascript" src="' . $wa->getRootUrl() . 'wa-content/js/jquery-ui/jquery-ui.custom.min.js"></script>'."\n";
                            }
                            // include locale
                            if ($ui_custom[$jquery_f] && $locale != 'en_US') {
                                $result .= '<script type="text/javascript" src="' . $wa->getRootUrl() . 'wa-content/js/jquery-ui/i18n/jquery.ui.'.$jquery_f.'-'.$locale.'.js"></script>'."\n";
                            }
                            continue;
                        }
                    }
                    if (!file_exists($f)) {
                        $r = false;
                        break;
                    }
                    $files_combine[] = $f;
                    if ($mtime && filemtime($root_path.'/'.$f) > $mtime) {
                        $mtime = 0;
                    }
                }
            }
            if ($r && !$mtime) {
                $data = "";
                foreach ($files_combine as $file) {
                    $data .= file_get_contents($root_path.'/'.$file).";\n";
                }
                waFiles::create($app_path.'/'.$params['file']);
                $r = @file_put_contents($app_path.'/'.$params['file'], $data);
            }
            if ($r) {
                $result .= '<script type="text/javascript" src="' . $wa->getAppStaticUrl().$params['file'] . '?v'.$wa->getVersion().'"></script>'."\n";
                return $result;
            }
        }
        
        $result = "";
        foreach ($files as $f) {
            $f = trim($f);
            if ($f) {
                $result .= '<script type="text/javascript" src="' . $f . '"></script>'."\n";
                if (substr($f, $n) == $jquery_ui_path.'datepicker.min.js' && $locale != 'en_US') {
                    $result .= '<script type="text/javascript" src="' . $wa->getRootUrl() . 'wa-content/js/jquery-ui/i18n/jquery.ui.datepicker-'.$locale.'.js"></script>'."\n";
                }
            }
        }
        return $result;
    }
