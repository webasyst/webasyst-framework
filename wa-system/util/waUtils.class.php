<?php

class waUtils
{
    public static function varExportToFile($var, $file, $export  = true)
    {
        // try write to tmp file and rename
        $tmp_file = tempnam(dirname($file), $file);
        if (is_writable(dirname($tmp_file))) {
            if ($h = @fopen($tmp_file, 'w+')) {
                if (flock($h, LOCK_EX)) {
                    $result = fwrite($h, "<?php\nreturn " . ($export ? var_export($var, true) : $var) . ";\n");
                    fflush($h);
                    flock($h, LOCK_UN);
                } else {
                    $result = false;
                }
                fclose($h);
                if ($result) {
                    if (@rename($tmp_file, $file)) {
                        return true;
                    }
                }
            }
        }
        if (!file_exists($file) || is_writable($file)) {
            if ($h = @fopen($file, 'w+')) {
                if (flock($h, LOCK_EX)) {
                    $result = fwrite($h, "<?php\nreturn " . ($export ? var_export($var, true) : $var) . ";\n");
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
