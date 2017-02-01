<?php

class waUtils
{
    public static function varExportToFile($var, $file, $export = true)
    {
        $result = false;
        if ($export) {
            $var = var_export($var, true);
        }
        $dir = realpath(dirname($file));
        $file_contents = "<?php\nreturn {$var};\n";

        // Attempt to write to tmp file and then rename.
        // This minimizes the risk that a half-written file will be
        // included by another process if something goes wrong.
        $tmp_file = tempnam($dir, basename($file));
        if ($tmp_file && $dir == realpath(dirname($tmp_file))) {
            @chmod($tmp_file, 0664);
            $result = @file_put_contents($tmp_file, $file_contents);
            $result = $result && @rename($tmp_file, $file);
        }
        if (file_exists($tmp_file)) {
            @unlink($tmp_file);
        }

        // Attempt to write to destination directly.
        if (!$result && (!file_exists($file) || is_writable($file))) {
            $result = @file_put_contents($file, $file_contents, LOCK_EX);
        }

        // Clear opcache so that file changes are visible to `include` immediately
        if ($result && function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
        return !!$result;
    }
}
