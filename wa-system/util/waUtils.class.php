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

    /**
     * @param array $array
     * @param mixed $field
     * @param string $type 'collect', 'first', 'last'
     * @return array
     * @since 1.10.0
     */
    public static function groupBy(array $array, $field, $type = 'collect')
    {
        $grouped = array();
        foreach ($array as $k => $ar) {

            if (is_scalar($ar)) {
                continue;
            }

            // For objects we use isset(). For arrays we use array_key_exists().
            $key_exists = isset($ar[$field]);
            if (!$key_exists && is_array($ar)) {
                $key_exists = array_key_exists($field, $ar);
            }

            if (!$key_exists) {
                continue;
            }

            $value = $ar[$field];

            if ($type === 'collect') {
                $grouped[$value] = isset($grouped[$value]) ? $grouped[$value] : array();
                $grouped[$value][$k] = $ar;
            } elseif ($type === 'first' && !isset($grouped[$value])) {
                $grouped[$value] = $ar;
            } elseif ($type === 'last') {
                $grouped[$value] = $ar;
            }
        }

        return $grouped;
    }

    /**
     * Re-order keys of input associative array by predefined order
     *
     * @param array $array that what you want re-order
     * @param array $order predefined order
     *
     * That keys IN $array that aren't in $order will be saved in original order
     * That values IN $orders that aren't keys in $array will be ignored
     *
     * Example
     *
     * $fruits = array('apple' => 100, 'orange' => 200, 'pineapple' => 300, 'watermelon' => 400);
     * $my_order = array('watermelon', 'pineapple', 'strawberry');
     * $fruits = waUtils::orderKeys($fruits, $my_order);
     * $fruits === array('watermelon' => 400, 'pineapple' => 300, 'apple' => 100, 'orange' => 200);
     *
     * @return array
     * @since 1.10.0
     */
    public static function orderKeys(array $array, $order = array())
    {
        if (is_scalar($order)) {
            $order = array($order);
        }
        $is_traversable = is_array($order) || $order instanceof Traversable;
        if (!$is_traversable || empty($order)) {
            return $array;
        }

        $array_keys = array_keys($array);

        $weighted_array_keys = array_fill_keys($array_keys, -1);

        $sort = 0;
        foreach ($order as $field) {
            if (isset($weighted_array_keys[$field])) {
                $weighted_array_keys[$field] = $sort++;
            }
        }
        foreach ($weighted_array_keys as $key => $value) {
            if ($value === -1) {
                $weighted_array_keys[$key] = $sort++;
            }
        }

        asort($weighted_array_keys, SORT_NUMERIC);
        $array_keys = array_keys($weighted_array_keys);

        $result = array();
        foreach ($array_keys as $key) {
            $result[$key] = $array[$key];
        }
        return $result;
    }

    /**
     * Extract from input associative array  only specified keys
     * @param $array
     * @param array $keys
     * @param bool $skip Default True
     * @param mixed $populate
     *
     * That values of $keys that aren't keys in $array will be
     *  + skipped - If $skip === True (default value)
     *  + not skipped - If $skip === False
     *  + populate by $populate value if $skip === False
     *
     * Example with skipping
     *
     * $fruits = array('apple' => 100, 'orange' => 200, 'pineapple' => 300, 'watermelon' => 400);
     * $keys = array('orange', 'watermelon', 'strawberry');
     * $fruits = waUtils::extractValuesByKeys($fruits, $keys);
     * $fruits === array('orange' => 200, 'watermelon' => 400);
     *
     * Example with populate
     *
     * $fruits = array('apple' => 100, 'orange' => 200, 'pineapple' => 300, 'watermelon' => 400);
     * $keys = array('orange', 'watermelon', 'strawberry');
     * $fruits = waUtils::extractValuesByKeys($fruits, $keys, false, 0);
     * $fruits === array('orange' => 200, 'watermelon' => 400, 'strawberry' => 0);
     *
     * @return array
     * @since 1.10.0
     */
    public static function extractValuesByKeys(array $array, $keys = array(), $skip = true, $populate = null)
    {
        $result = array();
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $result[$key] = $array[$key];
            } elseif (!$skip) {
                $result[$key] = $populate;
            }
        }
        return $result;
    }

    /**
     * Cast to array of integers
     * @param mixed $val
     * @return int[]
     * @since 1.10.0
     */
    public static function toIntArray($val)
    {
        $callback = 'return is_scalar($i) ? intval($i) : 0;';
        $callback = wa_lambda('$i', $callback);
        if (!is_scalar($val) && !is_array($val)) {
            $val = array();
        }
        return array_map($callback, (array)$val);
    }

    /**
     * Cast to array of strings
     * @param mixed $val
     * @param bool $trim
     * @return string[]
     * @since 1.10.0
     */
    public static function toStrArray($val, $trim = true)
    {
        $callback = 'return is_scalar($s) ? strval($s) : "";';
        if ($trim === true) {
            $callback = 'return is_scalar($s) ? trim(strval($s)) : "";';
        }
        $callback = wa_lambda('$s', $callback);
        if (!is_scalar($val) && !is_array($val)) {
            $val = array();
        }
        return array_map($callback, (array)$val);
    }

    /**
     * Drop all not positive values from input array
     * @param array [int] $int_array
     * @return array[int]
     * @since 1.10.0
     */
    public static function dropNotPositive($int_array)
    {
        foreach ($int_array as $index => $int) {
            if ($int <= 0) {
                unset($int_array[$index]);
            }
        }
        return $int_array;
    }

    /**
     * Wraps json_encode() adding proper options if available depending on PHP version.
     * @param mixed $value
     * @param int $options
     * @param int $depth
     * @return string
     * @since 1.10.0
     */
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
     * Wraps json_decode() adding error handling via exceptions
     *
     * @param string $json The json string being decoded
     * @param bool $assoc When TRUE, returned objects will be converted into associative arrays
     * @param int $depth User specified recursion depth (since PHP 5.3.0)
     * @param int $options Bitmask of JSON decode options (since PHP 5.4.0)
     * @return mixed
     * @throws waException
     * @since 1.10.0
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

    /**
     * Insert a value or key/value pair after a specific key in an array.
     * If key doesn't exist, value is appended to the end of the array.
     *
     * @param array $array
     * @param string $key
     * @param array $new
     * @return array
     * @since 1.10.0
     */
    public static function arrayInsertAfter(array $array, $key, array $new)
    {
        $keys = array_keys($array);
        $index = array_search($key, $keys);
        $pos = false === $index ? count($array) : $index + 1;
        return array_merge(array_slice($array, 0, $pos), $new, array_slice($array, $pos));
    }
}
