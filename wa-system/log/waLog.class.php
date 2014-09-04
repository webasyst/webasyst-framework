<?php

/** Ad hoc logging to /wa-log/$file */
class waLog
{
    public static function log($message, $file = 'error.log') {

        $path = waConfig::get('wa_path_log');
        if (!$path) {
            $path = dirname(dirname(dirname(__FILE__)));
        }
        $path .= '/'.$file;

        if (!file_exists($path)) {
            waFiles::create(dirname($path));
            touch($path);
            chmod($path, 0666);
        } elseif (!is_writable($path)) {
            return false;
        }

        $fd = fopen($path, 'a');
        if (!flock($fd, LOCK_EX)) {
            throw new waException('Unable to lock '.$path);
        }
        fwrite($fd, "\n");
        fwrite($fd, date('Y-m-d H:i:s: '));
        fwrite($fd, $message);
        fflush($fd);
        flock($fd, LOCK_UN);
        fclose($fd);
        return true;
    }
    
    public static function deprecated($message) {
        
        if(version_compare(PHP_VERSION, "5.3.0", ">=")) {
            trigger_error( $message, E_USER_DEPRECATED);
        } else {
            trigger_error( "Deprecated " . $message, E_USER_NOTICE);
        }
    }
}

// EOF
