<?php

class waUtils
{
    public static function varExportToFile($var, $file, $export = true)
    {
        $result = false;
        if ($export) {
            $var = var_export($var, true);
        }
        $file_contents = "<?php\nreturn {$var};\n";

        // Attempt to write to tmp file and then rename.
        // This minimizes the risk that a half-written file will be
        // included by another process if something goes wrong.
        $dir = realpath(dirname($file));
        if ($dir) {
            $tmp_file = @tempnam($dir, basename($file));
            if ($tmp_file && $dir == realpath(dirname($tmp_file))) {
                @chmod($tmp_file, 0664);
                $result = @file_put_contents($tmp_file, $file_contents);
                $result = $result && @rename($tmp_file, $file);
            }
            if ($tmp_file && file_exists($tmp_file)) {
                @unlink($tmp_file);
            }
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

    /**
     * Extract values of specific field(key) from array of array
     * Work kinda like array_column (php version < 7.0)
     * @param array $array array of associative arrays
     * @param string|int $field Key for extract value
     * @param string|int|null $index_key Key use as the index for the returned array
     * @return array[]string array of unique values
     */
    public static function getFieldValues(array $array, $field, $index_key = null)
    {
        $values = array();
        foreach ($array as $elem) {
            $elem = (array) $elem;
            $index_value = null;
            if ($index_key !== null && array_key_exists($index_key, $elem)) {
                $index_value = $elem[$index_key];
            }
            if (array_key_exists($field, $elem)) {
                if ($index_value === null) {
                    $values[] = $elem[$field];
                } else {
                    $values[$index_value] = $elem[$field];
                }
            }
        }
        return $index_key !== null ? array_unique($values) : $values;
    }
}
