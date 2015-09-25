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

    //
    // Non-debug mode: merge all files into one cache
    //
    if ((defined('DEBUG_WA_JS') || !SystemConfig::isDebug()) && isset($params['file'])) {
        $root_path = str_replace('\\', '/', $wa->getConfig()->getRootPath());
        $app_path = str_replace('\\', '/', $wa->getConfig()->getAppPath());
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
                            $result = '<script type="text/javascript" src="' . $wa->getRootUrl() . 'wa-content/js/jquery-ui/jquery-ui.custom.min.js?v'.$wa->getVersion('webasyst').'"></script>'."\n";
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
        if ($files_combine) {
            if ($r && !$mtime && waFiles::create($app_path.'/'.$params['file'])) {
                // check Google Closure Compiler
                // https://developers.google.com/closure/compiler/docs/gettingstarted_app

                if ($compiler = waSystemConfig::systemOption('js_compiler')) {
                    $create_source_map = waSystemConfig::systemOption('js_compiler_source_map');
                    $cmd = 'java -jar "'.$compiler.'" --summary_detail_level 0';
                    if ($create_source_map) {
                         $cmd .= ' --create_source_map "%outname%.map" --source_map_format=V3';
                    }
                    foreach ($files_combine as $file) {
                        $cmd .= ' --js "'.$root_path.'/'.$file.'"';
                    }
                    $cmd .= ' --js_output_file "'.$app_path.'/'.$params['file'].'"';
                    system($cmd,$res);
                    $r = !$res;
                    if(!$r) {
                        waLog::log("Error occured while compress files:\n\t".implode("\n\t",$files_combine)."\n\t{$params['file']}\n\ncommand:\n{$cmd}",__FUNCTION__.'.log');
                    } else if ($create_source_map && file_exists($app_path.'/'.$params['file'].'.map')) {
                        if ( ( $contents = @file_get_contents($app_path.'/'.$params['file']))) {
                            $contents = rtrim($contents)."\n//# sourceMappingURL=".basename($params['file']).".map";
                            @file_put_contents($app_path.'/'.$params['file'], $contents);
                        }
                        if ( ( $contents = @file_get_contents($app_path.'/'.$params['file'].'.map'))) {
                            $contents = str_replace($root_path.'/', wa()->getRootUrl(), $contents);
                            @file_put_contents($app_path.'/'.$params['file'].'.map', $contents);
                        }
                    }
                } else {
                    $r = false;
                }
                if(!$r) {
                    $data = "";
                    foreach ($files_combine as $file) {
                        $data .= file_get_contents($root_path.'/'.$file).";\n";
                    }
                    $r = @file_put_contents($app_path.'/'.$params['file'], $data);
                    if(!$r) {
                        waLog::log("Error occured while compress files:\n\t".implode("\n\t",$files_combine)."\n\t{$params['file']}",__FUNCTION__.'.log');
                    }
                }
            }
        }
        if ($r) {
            if ($files_combine) {
                $result .= '<script type="text/javascript" src="' . $wa->getAppStaticUrl().$params['file'] . '?v'.$wa->getVersion().'"></script>'."\n";
            }
            return $result;
        }
    }

    //
    // Debug mode (or no file specified): include all files separately
    //
    $result = "";
    foreach ($files as $f) {
        $f = trim($f);
        if ($f) {
            // Add ?version to circumvent browser caching
            if (substr($f, $n, 10) !== 'wa-content') {
                $f .= '?v' . $wa->getVersion();
            }

            $result .= '<script type="text/javascript" src="' . $f . '"></script>'."\n";

            // Add datepicker localization automatically
            if (substr($f, $n) == $jquery_ui_path.'datepicker.min.js' && $locale != 'en_US') {
                $result .= '<script type="text/javascript" src="' . $wa->getRootUrl() . 'wa-content/js/jquery-ui/i18n/jquery.ui.datepicker-'.$locale.'.js"></script>'."\n";
            }
        }
    }
    return $result;
}

