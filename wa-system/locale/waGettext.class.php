<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage locale
 */

class waGettext
{
    protected $type;
    protected $file;
    protected $all;

    public function __construct($file, $all = false)
    {
        $this->all = $all;
        $this->file = $file;
        $path_info = pathinfo($this->file);
        $this->type = $path_info['extension'];
    }

    public function read()
    {
        if ($this->type == 'mo') {
            return $this->readMo();
        } else {
            return $this->readPo();
        }
    }

    public function getMessagesMetaPlurals()
    {
        $parsed = $this->parseFile();
        $messages = ifset($parsed, 'messages', []);

        $meta = $this->meta2array(isset($messages['']) ? $messages[''] : '', true);
        unset($messages['']);
        return array(
            'meta'     => $meta,
            'messages' => $messages,
            'plurals'  => ifset($parsed, 'plurals', [])
        );
    }

    protected function readPo()
    {
        $parsed = $this->parseFile();
        $messages = ifset($parsed, 'messages', []);

        $meta = $this->meta2array(isset($messages['']) ? $messages[''] : '');
        unset($messages['']);
        return array(
            'meta'     => $meta,
            'messages' => $messages,
        );
    }

    protected function parseFile()
    {
        $file = $this->file;
        if (!$contents = @file($file)) {
            return [];
        }
        $messages = [];
        $plurals = [];
        $buffer = [];

        foreach ($contents as $string) {
            $string = trim($string);
            if (!$string || substr($string, 0, 1) == '#') {
                continue;
            }

            if (substr($string, 0, 12) == 'msgid_plural') {
                $buffer['msgid_plural'] = $this->prepare(substr($string, 13));
            } elseif (substr($string, 0, 5) == 'msgid') {
                if ($buffer) {
                    if (isset($buffer['msgid']) && (!empty($buffer['msgstr']) || $this->all)) {
                        $messages[$buffer['msgid']] = isset($buffer['msgstr']) ? $buffer['msgstr'] : '';
                        if (isset($buffer['msgid_plural'])) {
                            $plurals[$buffer['msgid']] = $buffer;
                        }
                    }
                    $buffer = array();
                }
                $buffer['msgid'] = $this->prepare(substr($string, 6));
            } elseif (substr($string, 0, 6) == 'msgstr') {
                if (!$buffer) {
                    continue;
                }
                if (isset($buffer['msgid_plural'])) {
                    if ($msgstr = $this->prepare(substr($string, 10))) {
                        $buffer['msgstr'][substr($string, 7, 1)] = $msgstr;
                    }
                } else {
                    $buffer['msgstr'] = $this->prepare(substr($string, 7));
                }
            } elseif (substr($string, 0, 1) == '"') {
                if (isset($buffer['msgid_plural'])) {
                    if (!isset($buffer['msgstr'])) {
                        $buffer['msgstr'] = array('');
                    }
                    $buffer['msgstr'][count($buffer['msgstr']) - 1] .= $this->prepare($string);
                } else {
                    if (!isset($buffer['msgstr'])) {
                        $buffer['msgid'] .= $this->prepare($string);
                    } else {
                        $buffer['msgstr'] .= $this->prepare($string);
                    }
                }
            }
        }

        if (isset($buffer['msgid']) && (!empty($buffer['msgstr']) || $this->all)) {
            $messages[$buffer['msgid']] = isset($buffer['msgstr']) ? $buffer['msgstr'] : '';
            if (isset($buffer['msgstr'])) {
                $plurals[$buffer['msgid']] = $buffer;
            }
        }

        $result = [
            'messages' => $messages,
            'plurals'  => $plurals,
        ];

        return $result;
    }

    protected function readMo()
    {
        return array();
    }

    protected function prepare($string, $reverse = false)
    {
        $string = trim($string);
        if (substr($string, 0, 1) == '"' && substr($string, -1) == '"') {
            $string = substr($string, 1, -1);
        }
        $smap = array('\\n', '\\r', '\\t', '\"');
        $rmap = array("\n", "\r", "\t", '"');
        return str_replace($smap, $rmap, $string);
    }

    public function meta2array($meta, $default_plural = false)
    {
        $array = array();
        foreach (explode("\n", $meta) as $info) {
            if ($info = trim($info)) {
                list($key, $value) = explode(':', $info, 2) + ['', ''];
                $array[trim($key)] = trim($value);
            }
        }
        if (isset($array['Plural-Forms'])) {
            $data = explode(";", $array['Plural-Forms']);
            $array['Plural-Forms'] = array();
            foreach ($data as $s) {
                if (trim($s)) {
                    $s = explode("=", trim($s), 2);
                    $array['Plural-Forms'][$s[0]] = $s[1];
                }
            }

            if (!$default_plural) {
                $array['Plural-Forms']['plural'] = 'return '.str_replace('n', '$n', $array['Plural-Forms']['plural']).';';
            }
        }

        return $array;
    }
}
