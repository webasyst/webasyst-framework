<?php

class waUtils
{
    public static function varExportToFile($var, $file, $export  = true)
    {
        if (!file_exists($file) || is_writable($file)) {
            if ($h = @fopen($file, 'w+')) {
                if (flock($h, LOCK_EX)) {
                    $result = fwrite($h, "<?php\nreturn ".($export ? var_export($var, true) : $var).";");
                    fflush($h);
                    flock($h, LOCK_UN);
                } else {
                    $result = false;
                }
                fclose($h);
                return $result;
            }
        }
        return false;
    }

}