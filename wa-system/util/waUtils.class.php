<?php

class waUtils
{
    /**
     * Saves a variable value to a PHP configuration file.
     * The saved value can be read from the file using include() statement.
     *
     * @param  mixed  $var    Variable value.
     * @param  string $file   Path to the file.
     * @param  bool   $export Whether the value must be converted to a string using var_export() function.
     * @return bool           Whether the value was successfully saved.
     */
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
     * Extract values from an array of arrays (or objects) using a single key.
     * Works kinda like array_column (which unfortunately requires php 7).
     *
     * When used with objects, requires having magic methods __isset() and __get().
     *
     * Will return array containing elements $array[$i][$field], indexed by one of several things.
     * When $index_key is null, it means gather everything and return array_unique() of that.
     * When $index_key is true, it means to use index $i of original $array.
     * Otherwise, index by value taken from $array[$i][$index_key]. If not present, value is skipped.
     *
     * @param array      $array      Array of arrays or objects.
     * @param string|int $field      Key for extracted values.
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
     * Groups sub-arrays of an array in several ways.
     *
     * @param array  $array Array of associative sub-arrays.
     * @param mixed  $field Key of sub-arrays' items by whose values sub-arrays will be grouped.
     * @param string $type Type of grouping: 'collect', 'first', or 'last':
     * - 'collect': sub-arrays of the original array, containing equal values of the key specified in $field parameter, are gruoped into sub-arrays of the resulting array; the values of the specified $field are used as the keys of the resulting array.
     * - 'first': only the first sub-array of each group of sub-arrays with the same values of the specified $field is added to the resulting array.
     * - 'last': only the last sub-array of each group of sub-arrays with the same values of the specified $field is added to the resulting array.
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
     * Re-order keys of input associative array by predefined order.
     *
     * @param array $array Original array.
     * @param array $order Array of its keys in the desired order.
     *
     * The order of keys of $array that are not contained in $order will not be changed.
     * The values of $order that are not among the keys of $array will be ignored.
     *
     * @example
     * $array = array('apple' => 100, 'orange' => 200, 'pineapple' => 300, 'watermelon' => 400);
     * $order = array('watermelon', 'pineapple', 'strawberry');
     * waUtils::orderKeys($array, $order);
     * // array('watermelon' => 400, 'pineapple' => 300, 'apple' => 100, 'orange' => 200);
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
     * Returns array items with specified keys.
     *
     * @param array $array    Associative array.
     * @param array $keys     Array of keys whose values must be returned.
     * @param bool  $skip     Whether array items with keys missing in $keys must not be included in the resulting array.
     * @param mixed $populate The value to replace the values of original array’s items,
     * whose keys are not contained in $keys, when $skip = false.
     *
     * The values of $keys that are not among the keys of $array will be
     *  + skipped - If $skip === True (default value)
     *  + not skipped - If $skip === False
     *  + populated by $populate value if $skip === False
     *
     * @example $skip = true
     *
     * $array = array('apple' => 100, 'orange' => 200, 'pineapple' => 300, 'watermelon' => 400);
     * $keys = array('orange', 'watermelon', 'strawberry');
     * waUtils::extractValuesByKeys($array, $keys);
     * // array('orange' => 200, 'watermelon' => 400);
     *
     * @example $populate = true
     *
     * $array = array('apple' => 100, 'orange' => 200, 'pineapple' => 300, 'watermelon' => 400);
     * $keys = array('orange', 'watermelon', 'strawberry');
     * waUtils::extractValuesByKeys($array, $keys, false, 0);
     * // array('orange' => 200, 'watermelon' => 400, 'strawberry' => 0);
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
     * Returns an array of values cast to integer type.
     *
     * @param mixed $val Either array of values,
     *     or a scalar value which, cast to integer type, is returned as a single array item.
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
     * Returns an array of values cast to string type.
     *
     * @param mixed $val  Either array of values,
     *                    or a scalar value which, cast to string type, is returned as a single array item.
     * @param bool  $trim Whether leading and trailing whitespace characters must be removed from strings.
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
     * Filters out array items which are not positive numbers.
     *
     * @param array [numeric] $array Original array of numbers.
     * @return array[numeric]
     * @since 1.10.0
     */
    public static function dropNotPositive($array)
    {
        foreach ($array as $index => $int) {
            if ($int <= 0) {
                unset($array[$index]);
            }
        }
        return $array;
    }

    /**
     * Wrapper for json_encode() function adding proper options if available for current PHP version.
     *
     * @param mixed $value
     * @param int   $options
     * @param int   $depth
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
     * Wrapper for json_decode() function with error handling via exceptions.
     *
     * @param string $json The JSON string to be decoded.
     * @param bool   $assoc When TRUE, returned objects will be converted into associative arrays.
     * @param int    $depth User specified recursion depth (since PHP 5.3.0).
     * @param int    $options Bitmask of JSON decode options (since PHP 5.4.0).
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
     * Adds new array items after a specified key.
     * If the specified key doesn't exist, new values are appended to the end of the array.
     *
     * @param array  $array Original associative array.
     * @param string $key   Array key after which new items must be added.
     * @param array  $new   New items array.
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

    /**
     * Generate random hex string of length $length
     * Try use the most cryptographically secure algorithm available in current php version
     *
     * @param int $length
     * If input is of invalid type (not int greater then 0) will be force to $length = 64,
     * Default is 64
     *
     * @return string
     */
    public static function getRandomHexString($length = 64)
    {
        if (!wa_is_int($length) || $length <= 0) {
            $length = 64;
        }

        // we will bin2hex and byte is 2 hex digit, so make a little correction and than, before method returns, make correction back
        $is_even = $length % 2 === 0;
        if (!$is_even) {
            $length += 1;
        }

        if (function_exists('random_bytes')) {
            try {

                $result = bin2hex(random_bytes($length / 2));

                // make a correction back
                if (!$is_even) {
                    $result = substr($result, 1);
                }

                return $result;
            } catch (Exception $e) {
            }
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $result = openssl_random_pseudo_bytes($length / 2);
            if ($result) {
                $result = bin2hex($result);

                // make a correction back
                if (!$is_even) {
                    $result = substr($result, 1);
                }

                return $result;
            }
        }

        $bytes = [];
        if (function_exists('random_int')) {
            $fn = 'random_int';
        } elseif (function_exists('mt_rand')) {
            $fn = 'mt_rand';
        } else {
            $fn = 'rand';
        }

        for ($i = 0, $n = $length / 2; $i < $n; $i++) {
            $bytes[] = chr($fn(0, 255));   // gen one byte
        }

        $bytes = join('',$bytes);

        $result = bin2hex($bytes);

        // make a correction back
        if (!$is_even) {
            $result = substr($result, 1);
        }

        return $result;
    }

    /**
     * Url safe base64 encoding
     * @param $string
     * @return string
     */
    public static function urlSafeBase64Encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(['+','/','='], ['-','_',''], $data);
        return $data;
    }

    /**
     * Url safe base64 decoding
     * @param $string
     * @return false|string
     */
    public static function urlSafeBase64Decode($string)
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    /**
     * Check is string already encoded
     * @param $string
     * @return bool
     */
    public static function isUrlSafeBase64Encoded($string)
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data, true) !== false;
    }

   /**
     * Greater common divisor of two positive integers.
     * @since 1.13.9
     */
    public static function gcd($a, $b) {
        $a = max((int) $a, 1);
        $b = max((int) $b, 1);
        while ($b != 0) {
            list($a, $b) = [$b, $a % $b];
        }
        return $a;
    }

    /**
     * Least common multiple of two positive integers.
     * @since 1.13.9
     */
    public static function lcm($a, $b) {
        $a = max((int) $a, 1);
        $b = max((int) $b, 1);
        return $a*$b / self::gcd($a, $b);
    }
}
