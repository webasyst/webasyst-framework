<?php

function smarty_block_wa_js($params, $content, &$smarty)
{
    if (!$content) {
        return '';
    }
    // jquery ui custom bundle
    $ui_custom = array(
        'core'       => 0,
        'widget'     => 0,
        'mouse'      => 0,
        'draggable'  => 0,
        'droppable'  => 0,
        'sortable'   => 0,
        'datepicker' => 1,
    );

    // Add .js extension to filename to be written, if it ends in anything else
    if (!empty($params['file'])) {
        $recordable_file = pathinfo($params['file']);
        if (ifset($recordable_file['extension']) !== 'js') {
            $params['file'] = $recordable_file['dirname'].'/'.$recordable_file['filename'].'.js';
        }
    }

    $files = explode("\n", $content);
    $files = array_map('trim', $files);
    $files = array_filter($files);
    foreach ($files as &$file) {
        $file = trim($file);
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'js') {
            $file = null;
        }
        if (preg_match('@(\\\\|/|^)\.\.(\\\\|/)@', $file)) {
            $file = null;
        }
    }
    $files = array_filter($files);

    $wa = waSystem::getInstance();

    $root_path = str_replace('\\', '/', $wa->getConfig()->getRootPath());
    $jquery_ui_path = "wa-content/js/jquery-ui/jquery.ui.";
    $jquery_ui_path_n = strlen($jquery_ui_path);

    $n = strlen($wa->getRootUrl());

    $locale = $wa->getLocale();

    //
    // Non-debug mode: merge all files into one cache
    //
    if ((defined('DEBUG_WA_JS') || !SystemConfig::isDebug()) && isset($params['file'])) {
        $params['uibundle'] = ifset($params['uibundle'], true);
        $app_path = str_replace('\\', '/', $wa->getConfig()->getAppPath());
        $result = '';
        $files_combine = array();
        $mtime = file_exists($app_path.'/'.$params['file']) ? filemtime($app_path.'/'.$params['file']) : 0;
        $wa_version = $wa->getVersion('webasyst');
        $r = true;
        foreach ($files as $f) {
            $f = substr($f, $n);
            if (!$f) {
                continue;
            }

            // Do not allow to minify files from wa-data because they're user-writable,
            // and from wa-config to prevent disclosure of sensitive data
            $folders = explode("/", ltrim($f, './\\'));
            if ($folders[0] == 'wa-data' || $folders[0] == 'wa-config') {
                continue;
            }

            if ($params['uibundle'] && substr($f, 0, $jquery_ui_path_n) == $jquery_ui_path) {
                $jquery_f = substr($f, $jquery_ui_path_n);
                if (substr($jquery_f, -7) == '.min.js') {
                    $jquery_f = substr($jquery_f, 0, -7);
                }
                if (isset($ui_custom[$jquery_f])) {
                    if (!$result) {
                        $result = '<script type="text/javascript" src="'.$wa->getRootUrl().'wa-content/js/jquery-ui/jquery-ui.custom.min.js?v'.$wa_version.'"></script>'."\n";
                    }
                    // include locale
                    if ($ui_custom[$jquery_f] && $locale != 'en_US') {
                        $result .= '<script type="text/javascript" src="'.$wa->getRootUrl().'wa-content/js/jquery-ui/i18n/jquery.ui.'.$jquery_f.'-'.$locale.'.js?v'.$wa_version.'"></script>'."\n";
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
                    system($cmd, $res);
                    $r = !$res;
                    if (!$r) {
                        waLog::log("Error occured while compress files:\n\t".implode("\n\t", $files_combine)."\n\t{$params['file']}\n\ncommand:\n{$cmd}", __FUNCTION__.'.log');
                    } elseif ($create_source_map && file_exists($app_path.'/'.$params['file'].'.map')) {
                        if (($contents = @file_get_contents($app_path.'/'.$params['file']))) {
                            $contents = rtrim($contents)."\n//# sourceMappingURL=".basename($params['file']).".map";
                            @file_put_contents($app_path.'/'.$params['file'], $contents);
                        }
                        if (($contents = @file_get_contents($app_path.'/'.$params['file'].'.map'))) {
                            $contents = str_replace($root_path.'/', wa()->getRootUrl(), $contents);
                            @file_put_contents($app_path.'/'.$params['file'].'.map', $contents);
                        }
                    }
                } else {
                    $r = false;
                }
                if (!$r) {
                    $data = "";
                    foreach ($files_combine as $file) {
                        $data .= file_get_contents($root_path.'/'.$file).";\n";
                    }
                    $r = @file_put_contents($app_path.'/'.$params['file'], $data);
                    if (!$r) {
                        waLog::log("Error occured while compress files:\n\t".implode("\n\t", $files_combine)."\n\t{$params['file']}", __FUNCTION__.'.log');
                    }
                }
                $mtime = time();
            }
        }
        if ($r) {
            if ($files_combine) {
                // Several seconds after compilation do not use browser cache.
                // This hopefully fixes some weird cache-related bugs.
                $ver = min($mtime + 5, time());

                $result .= '<script type="text/javascript" src="'.$wa->getAppStaticUrl().$params['file'].'?v'.$ver.'"></script>'."\n";
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
            // Do not allow to minify files from wa-data because they're user-writable
            $folders = explode("/", ltrim($f, './\\'));
            if ($folders[0] == 'wa-data') {
                continue;
            }
            // Add ?version to circumvent browser caching
            if (substr($f, $n, 10) === 'wa-content') {
                if (!SystemConfig::isDebug() || strpos(substr($f, $n + 10), 'wa') !== false) {
                    $f .= '?v'.$wa->getVersion('webasyst');
                } else {
                    $file_path = $root_path.'/'.substr($f, $n);
                    if (file_exists($file_path)) {
                        $f .= '?v'.filemtime($file_path);
                    }
                }
            } else {
                $f .= '?v'.$wa->getVersion();
            }

            $result .= '<script type="text/javascript" src="'.$f.'"></script>'."\n";

            // Add datepicker localization automatically
            if (substr($f, $n) == $jquery_ui_path.'datepicker.min.js' && $locale != 'en_US') {
                $result .= '<script type="text/javascript" src="'.$wa->getRootUrl().'wa-content/js/jquery-ui/i18n/jquery.ui.datepicker-'.$locale.'.js"></script>'."\n";
            }
        }
    }
    return $result;
}
