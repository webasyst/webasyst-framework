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
                } elseif (isset($elem[$index_key])) {
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

    public static function jsonEncode($value, $options = 0, $depth = 512)
    {
        if (!$options) {
            if (defined('JSON_UNESCAPED_UNICODE')) {
                $options |= constant('JSON_UNESCAPED_UNICODE');
            }
            if (class_exists('waSystemConfig') && waSystemConfig::isDebug() && defined('JSON_PRETTY_PRINT')) {
                $options |= constant('JSON_PRETTY_PRINT');
            }
        }

        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            return json_encode($value, $options, $depth);
        } elseif (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            return json_encode($value, $options);
        } else {
            return json_encode($value);
        }
    }

    /**
     * @param string $json The json string being decoded
     * @param bool $assoc When TRUE, returned objects will be converted into associative arrays
     * @param int $depth User specified recursion depth (since PHP 5.3.0)
     * @param int $options Bitmask of JSON decode options (since PHP 5.4.0)
     * @return mixed
     * @throws waException
     */
    public static function jsonDecode($json, $assoc = false, $depth = 512, $options = 0)
    {
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $res = @json_decode($json, $assoc, $depth, $options);
        } elseif (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            $res = @json_decode($json, $assoc, $depth);
        } else {
            $res = @json_decode($json, $assoc);
        }

        if (function_exists('json_last_error')) {
            if (JSON_ERROR_NONE !== json_last_error()) {
                if (function_exists('json_last_error_msg')) {
                    $message = json_last_error_msg();
                } else {
                    $message = json_last_error();
                    $codes = array(
                        'JSON_ERROR_DEPTH'            => 'The maximum stack depth has been exceeded',
                        'JSON_ERROR_STATE_MISMATCH'   => 'Invalid or malformed JSON',
                        'JSON_ERROR_CTRL_CHAR'        => 'Control character error, possibly incorrectly encoded',
                        'JSON_ERROR_SYNTAX'           => 'Syntax error',
                        'JSON_ERROR_UTF8'             => 'Malformed UTF-8 characters, possibly incorrectly encoded',//PHP 5.3.3
                        'JSON_ERROR_RECURSION'        => 'One or more recursive references in the value to be encoded',//PHP 5.5.0
                        'JSON_ERROR_INF_OR_NAN'       => 'One or more NAN or INF values in the value to be encoded',//PHP 5.5.0
                        'JSON_ERROR_UNSUPPORTED_TYPE' => 'A value of a type that cannot be encoded was given',//PHP 5.5.0
                    );

                    foreach ($codes as $code => $_message) {
                        if (defined($code) && (constant($code) == $message)) {
                            $message = $_message;
                            break;
                        }
                    }

                }
                throw new waException('Error while decode JSON string: '.$message);
            }
        } elseif ($res === null) {
            throw new waException('Error while decode JSON string');
        }

        return $res;
    }
}
