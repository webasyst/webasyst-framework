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
     * Extract values from array of arrays (or objects) using a single key.
     * Works kinda like array_column (which unfortunately requires php 7).
     *
     * When used with objects, requires having magic methods __isset() and __get().
     *
     * Will return array containing elements $array[$i][$field], indexed by one of several things.
     * When $index_key is null, it means gather everything and return array_unique() of that.
     * When $index_key is true, it means to use index $i of original $array.
     * Otherwise, index by value taken from $array[$i][$index_key]. If not present, value is skipped.
     *
     * @param array      $array      Array of arrays or objects
     * @param string|int $field      Key for extract value
     * @param mixed      $index_key  How to index the result, see above.
     * @return array
     */
    public static function getFieldValues(array $array, $field, $index_key = null)
    {
        $values = array();
        foreach ($array as $k => $elem) {

            // Index in the resulting array
            $index_value = null;
            if ($index_key !== null) {
                if ($index_key === true) {
                    $index_value = $k;
                } else if (isset($elem[$index_key])) {
                    $index_value = $elem[$index_key];
                } else {
                    // Skip if we can't find value to index by
                    continue;
                }
            }

            // For objects we use isset(). For arrays we use array_key_exists().
            $key_exists = isset($elem[$field]);
            if (!$key_exists && is_array($elem)) {
                $key_exists = array_key_exists($field, $elem);
            }

            if ($key_exists) {
                if ($index_value === null) {
                    $values[] = $elem[$field];
                } else {
                    $values[$index_value] = $elem[$field];
                }
            }
        }

        if ($index_key === null) {
            $values = array_unique($values);
        }
        return $values;
    }
}
