<?php

class blogImportPlugin extends blogPlugin
{
    private static function verifyRegexp(&$pattern, $delimiter = '/')
    {
        $errors = array(
            -1                         => true,
            PREG_NO_ERROR              => _wp('Invalid regex'),
            PREG_INTERNAL_ERROR        => _wp('There was an internal PCRE error'),
            PREG_BACKTRACK_LIMIT_ERROR => _wp('Backtrack limit was exhausted'),
            PREG_RECURSION_LIMIT_ERROR => _wp('Recursion limit was exhausted'),
            PREG_BAD_UTF8_ERROR        => _wp('The offset didn\'t correspond to the begin of a valid UTF-8 code point'),

        );
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $errors[PREG_BAD_UTF8_OFFSET_ERROR] = _wp('Malformed UTF-8 data');
        }
        $res = @preg_match($pattern, null);

        if ($res === false) {
            $code = preg_last_error();
        } else {
            $code = -1;
        }
        return $errors[$code];
    }

    public function validateSettings(&$errors)
    {
        $valid = true;
        if (isset($this->settings['replace'])) {
            if (!empty($this->settings['replace']['search'])) {
                $replace = $this->settings['replace'];
                foreach ($replace['search'] as $id => $search) {
                    if (!empty($replace['is_regexp']) && !empty($replace['is_regexp'][$id])) {

                        $error = self::verifyRegexp($search);
                        $replace['search'][$id] = $search;
                        $this->settings['replace'] = $replace;

                        if ($error !== true) {
                            $valid = false;
                            $errors['replace][search][:'.$id] = sprintf_wp('Invalid regexp pattern: %s', $error);
                        }
                    }
                    unset($search);
                }
            }
        }
        return $valid;
    }

}