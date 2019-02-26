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
 * @subpackage mail
 */
class waMailDecode
{
    protected $options = array(
        'buffer_size' => 16384,
        'headers_only' => false,
        'max_attachments' => 10,
        'attach_path' => '',
    );

    const STATE_START = 1;
    const STATE_HEADER = 2;
    const STATE_HEADER_VALUE = 3;
    const STATE_PART = 4;
    const STATE_PART_HEADER = 5;
    const STATE_PART_DATA = 6;
    const STATE_END = 7;

    const TYPE_HEADER = 1;
    const TYPE_HEADER_VALUE = 2;
    const TYPE_PART = 3;
    const TYPE_ATTACH = 4;

    const TEMP_NEW_LINE = "@///NEW-LINE///@";

    protected $source;
    protected $state;

    protected $buffer = '';
    protected $buffer_offset = 0;

    protected $attachments = array();

    protected $parts = array();
    protected $part;
    protected $part_index = 0;
    protected $is_last = false;

    protected $body = array();

    protected $current_header;

    /**
     * For some headers need to prevent multiple decoding header value
     * Cause if call iconv several times on the same string it would be incorrect broken string as a final result
     * @var array string => int
     */
    protected $decoded_header_counter = array();

    public function __construct($options = array())
    {
        $this->options = $options + $this->options;
    }

    /** Parse a decoded email header into list of arrays [name => ..., email => ..., full => ...] */
    public static function parseAddress($header)
    {
        $v = $header;
        try {
            $parser = new waMailAddressParser($v);
            $v = $parser->parse();
        } catch (Exception $e) {
            if (preg_match('~<([^>]+)>~', $v, $m)) {
                $email = $m[1];
            } elseif (preg_match('~(\S+\@\S+)~', $v, $m)) {
                $email = $m[1];
            } else {
                $email = explode(' ', $v);
                $email = $email[0];
            }

            $name = trim(preg_replace('~<?'.preg_quote($email, '~').'>?~', '', $v));
            $v = array(array('name' => $name, 'email' => $email));
        }
        //$v[0]['full'] = $header;
        return $v;
    }

    public function decode($file, $full_response = false)
    {
        if (is_resource($file)) {
            $this->source = $file;
        } else {
            $this->source = fopen($file, 'r');
            $this->options['attach_path'] = dirname($file).'/files/';
        }
        // start state
        $this->buffer = '';
        $this->buffer_offset = 0;
        $this->is_last = false;
        $this->state = self::STATE_HEADER;
        $this->parts = array(array());
        $this->attachments = array();
        $this->part_index = 0;
        $this->body = array();
        $this->part = &$this->parts[0];
        // check end of file
        if (!feof($this->source)) {
            $part = false;
            while ($this->state != self::STATE_END) {
                if (!$part) {
                    if ($this->is_last) {
                        fclose($this->source);
                        throw new waException("Letter was not completed, but there are no more data in file.");
                    }
                    $this->read();
                }
                $part = $this->parse();
                if ($part && is_array($part)) {
                    $this->decodePart($part);
                }
            }
            if (!$this->is_last) {
                if (!$this->options['headers_only']) {
                    if ($this->options['max_attachments'] >= 0 && count($this->attachments) < $this->options['max_attachments']) {
                        fclose($this->source);
                        throw new waException("End of letter reached. There are some more data.");
                    }
                }
            }
        }
        fclose($this->source);

        $headers = $this->parts[0]['headers'];

        foreach ($headers as $h => &$v) {
            if (is_array($v)) {
                $v = implode(self::TEMP_NEW_LINE, $v);
            }


            if ($h == 'subject') {
                $v = $this->decodeHeader($v, $h, true);
            } else {
                $v = $this->decodeHeader($v, $h);
            }

            if ($h == 'subject') {
                if (strpos($v, ' ') === false) {
                    $v = str_replace('_', ' ', $v);
                }
            } elseif ($h == 'date') {
                $v = preg_replace("/[^a-z0-9:,\.\s\t\+-]/i", '', $v);
                $v = date("Y-m-d H:i:s", strtotime($v));
            } elseif ($h == 'to' || $h == 'cc' || $h == 'bcc') {
                $v = self::parseAddress($v);
            } elseif ($h == 'from' || $h == 'reply-to') {
                $v = self::parseAddress($v);
                if (isset($v[0])) {
                    $v = $v[0];
                }
            }
        }
        unset($v);
        foreach (array('subject','from', 'to', 'cc', 'bcc', 'reply-to', 'date') as $h) {
            if (!isset($headers[$h])) {
                $headers[$h] = '';
            }
        }
        $result = array_merge(array('headers' => $headers), $this->body);

        // return body
        if (isset($result['text/html'])) {
            $result['text/html'] = $this->cleanHTML($result['text/html']);
            if (!isset($this->body['text/plain']) || ($this->body['text/html'] && !trim($this->body['text/plain']))) {
                $result['text/plain'] = trim(strip_tags($result['text/html']));
            }
        }
        if (isset($this->body['text/plain'])) {
            $result['text/plain'] = trim($this->body['text/plain']);
            if (!isset($this->body['text/html'])) {
                $result['text/html'] = nl2br($result['text/plain']);
            }
        }
        // return attachments
        $result['attachments'] = $this->attachments;
        if ($full_response) {
            $result['parts'] = $this->parts;
        }
        return $result;
    }

    /**
     * Returns formatted Message-ID
     * @param array $headers
     * @return string
     */
    public function getMessageId($headers)
    {
        $message_id = isset($headers['message-id']) ? $headers['message-id'] : null;
        if ($message_id) {
            $message_id = trim($message_id, ' <>');
            // add @servername
            if (strpos($message_id, '@') === false) {
                $from = explode('@', $headers['from']['email']);
                if (isset($from[1])) {
                    $message_id .= '@'.$from[1];
                }
            }
        }
        return $message_id ? $message_id : null;
    }

    protected function cleanHTML($html_orig)
    {
        // body only
        $html = preg_replace("!^.*?<body[^>]*>(.*?)</body>.*?$!uis", "$1", $html_orig);

        // Being paranoid... In case UTF is not quite UTF.
        if (!$html) {
            $html = preg_replace("!^.*?<body[^>]*>(.*?)</body>.*?$!is", "$1", $html_orig);
        }

        // Remove <style> blocks
        $html = preg_replace("~<style[^>]*>.*?</style>~is", '', $html);

        // remove tags
        $html = trim(strip_tags($html, "<a><p><div><br><b><blockquote><strong><i><em><s><u><span><img><sup><font><sub><ul><ol><li><h1><h2><h3><h4><h5><h6><table><tr><td><th><hr><center>"));
        // realign javascript href to onclick
        $html = preg_replace("/href=(['\"]).*?javascript:(.*)?\\1/i", "onclick=' $2 '", $html);

        //remove javascript from tags
        if (preg_match('/javascript/i', $html)) {
            $pattern = "/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i";
            while (preg_match($pattern, $html, $m)) {
                $html = preg_replace($pattern, "<$1$3$4$5>", $html);
            }
        }
        if (preg_match('/:expr/i', $html)) {
            // dump expressions from contibuted content
            $html = preg_replace("/:expression\(.*?((?>[^(.*?)]+)|(?R)).*?\)\)/i", "", $html);

            $pattern = "/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i";
            while (preg_match($pattern, $html)) {
                $html = preg_replace($pattern, "<$1$3$4$5>", $html);
            }
        }
        // remove all on* events
        $pattern = "/<([^>]*)?[\s\r\n\t]on.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i";
        while (preg_match($pattern, $html)) {
            $html = preg_replace($pattern, "<$1$3>", $html);
        }
        return $html;
    }

    protected function explodeEmails($string)
    {
        $result = array();
        $email = '';
        $data = strtok($string, ',');
        do {
            $email .= $data;
            if (strpos($email, '@') !== false &&
                (strpos($email, '"') === false || (strpos($email, '"') !== strrpos($email, '"')))
            ) {
                $result[] = trim($email);
                $email = '';
            }
        } while ($data = strtok(','));
        return $result;
    }

    protected function read()
    {
        $this->buffer .= fread($this->source, $this->options['buffer_size']);
        $this->is_last = feof($this->source);
    }

    protected function parse()
    {
        switch ($this->state) {
            case self::STATE_HEADER:
                if ($this->buffer[$this->buffer_offset] == "\n" || substr($this->buffer, $this->buffer_offset, 2) == "\r\n") {
                    $this->state = self::STATE_PART;
                    return true;
                }
                // check string for --------\r\n
                $i1 = strpos($this->buffer, "\r", $this->buffer_offset);
                $i2 = strpos($this->buffer, "\n", $this->buffer_offset);
                if ($i1 !== false || $i2 !== false) {
                    if ($i1 === false) {
                        $i = $i2;
                    } elseif ($i2 === false) {
                        $i = $i1;
                    } else {
                        $i = min($i1, $i2);
                    }
                    $str = substr($this->buffer, $this->buffer_offset, $i - $this->buffer_offset);
                    if ($str === str_repeat("-", strlen($str))) {
                        $this->buffer_offset = $i;
                        $this->state = self::STATE_PART;
                        return true;
                    }
                }

                if (substr($this->buffer, $this->buffer_offset, 5) === 'From ') {
                    $this->buffer_offset += 5;
                    $this->state = self::STATE_HEADER_VALUE;
                    return array(
                        'type'  => self::TYPE_HEADER,
                        'value' => 'from ',
                    );
                }
                if (($i = strpos($this->buffer, ':', $this->buffer_offset)) !== false) {
                    // next state
                    $value = substr($this->buffer, $this->buffer_offset, $i - $this->buffer_offset);
                    $this->buffer_offset = $i + 1;
                    $this->state = self::STATE_HEADER_VALUE;
                    // return part info
                    return array(
                        'type'  => self::TYPE_HEADER,
                        'value' => strtolower(trim($value))
                    );
                } else {
                    // need more data
                    return false;
                }
            case self::STATE_HEADER_VALUE:
                $offset = $this->buffer_offset;
                $value = '';
                while (true) {
                    if (($i = strpos($this->buffer, "\n", $offset)) !== false) {
                        $i++;
                        $value .= substr($this->buffer, $offset, $i - $offset);
                        // multiline
                        if ($this->buffer[$i] == ' ' || $this->buffer[$i] == "\t") {
                            $offset = $i;
                        } else {
                            // next header
                            $this->buffer_offset = $i;
                            $this->state = self::STATE_HEADER;
                            return array(
                                'type'  => self::TYPE_HEADER_VALUE,
                                'value' => trim($value)
                            );
                        }
                    } else {
                        // need more data
                        return false;
                    }
                }
            case self::STATE_PART:
                if ($this->options['headers_only']) {
                    $this->state = self::STATE_END;
                    return false;
                }
                if (!isset($this->part['type'])) {
                    $this->part['type'] = 'text';
                    $this->part['subtype'] = 'plain';
                    $this->part['headers']['content-transfer-encoding'] = 'quoted-printable';
                }
                if ($this->part['type'] == 'multipart') {
                    $boundary = isset($this->part['params']['boundary']) ? '--'.$this->part['params']['boundary'] : "--";
                    if (($i = strpos($this->buffer, $boundary, $this->buffer_offset)) !== false) {
                        if (strlen($this->buffer) < $i + strlen($boundary)) {
                            return false;
                        }
                        $this->buffer_offset = $i + strlen($boundary);
                        if (substr($this->buffer, $this->buffer_offset, 2) == "--") {
                            if (isset($this->part['parent'])) {
                                $this->part_index = $this->part['parent'];
                                $this->part = &$this->parts[$this->part['parent']];
                                $this->buffer_offset += 2;
                                $this->skipLineBreak();
                                return true;
                            } else {
                                $this->state = self::STATE_END;
                            }
                            return false;
                        }
                        if (!$this->skipLineBreak()) {
                            $in = strpos($this->buffer, "\n", $this->buffer_offset);
                            if ($in === false) {
                                return false;
                            }
                            if ($this->buffer[$in - 1] == "\r") {
                                $in--;
                            }
                            if (($in - $this->buffer_offset) < 5) {
                                $this->buffer_offset = $in;
                            }
                            $this->skipLineBreak();
                        }

                        if ($this->is_last && $this->buffer_offset == strlen($this->buffer)) {
                            $this->state = self::STATE_END;
                            return true;
                        }

                        $this->parts[] = array('parent' => $this->part_index);
                        $this->part_index = count($this->parts) - 1;
                        $this->part = &$this->parts[$this->part_index];
                        $this->state = self::STATE_HEADER;
                    } else {
                        if ($this->is_last) {
                            $this->skipLineBreak();
                            $this->parts[] = array('parent' => $this->part_index, 'type' => 'text', 'subtype' => 'plain');
                            $this->part_index = count($this->parts) - 1;
                            $this->part = &$this->parts[$this->part_index];
                            $this->state = self::STATE_PART_DATA;
                            return true;
                        }
                        return false;
                    }
                } else {
                    $this->state = self::STATE_PART_DATA;
                }
                return true;
            case self::STATE_PART_DATA:
                if (isset($this->part['parent'])) {
                    // save applications
                    if ($this->attachments || $this->part['type'] != 'text' || isset($this->part['headers']['content-disposition'])) {
                        if (!$this->attachments && isset($this->part['headers']['content-disposition']) &&
                            $this->part['headers']['content-disposition'] == 'inline' && $this->part['type'] == 'text') {
                            // nothing
                        } else {
                            return array(
                                'type' => self::TYPE_ATTACH,
                                'value' => $this->buffer_offset,
                                'boundary' => isset($this->parts[$this->part['parent']]['params']['boundary']) ? $this->parts[$this->part['parent']]['params']['boundary'] : null,
                            );
                        }
                    }
                    // other parts
                    $boundary = isset($this->parts[$this->part['parent']]['params']['boundary']) ? "\n--".$this->parts[$this->part['parent']]['params']['boundary'] : "\n--";
                    if (($i = strpos($this->buffer, $boundary, $this->buffer_offset)) === false) {
                        if ($this->is_last) {
                            $this->state = self::STATE_END;
                            return array(
                                'type' => self::TYPE_PART,
                                'value' => substr($this->buffer, $this->buffer_offset)
                            );
                        }
                        // need more data
                        return false;
                    }
                    $value = substr($this->buffer, $this->buffer_offset, $i - $this->buffer_offset);
                    $this->buffer_offset = $i;
                    $this->state = self::STATE_PART;
                    return array(
                        'type' => self::TYPE_PART,
                        'value' => $value
                    );
                } else {
                    if (!$this->is_last) {
                        return false;
                    }
                    $this->state = self::STATE_END;
                    return array(
                        'type' => self::TYPE_PART,
                        'value' => substr($this->buffer, $this->buffer_offset)
                    );
                }
        }
        return false;
    }

    protected function skipLineBreak()
    {
        if ($this->buffer[$this->buffer_offset] == "\n") {
            $this->buffer_offset++;
            return true;
        } elseif (substr($this->buffer, $this->buffer_offset, 2) == "\r\n") {
            $this->buffer_offset += 2;
            return true;
        }
        return false;
    }

    protected static function quotedPrintableReplace($matches)
    {
        return  chr(hexdec($matches[1]));
    }

    protected function decodePart($part)
    {
        switch ($part['type']) {
            case self::TYPE_HEADER:
                $this->current_header = $part['value'];
                if (!isset($this->part['headers'][$part['value']])) {
                    $this->part['headers'][$part['value']] = '';
                }
                break;
            case self::TYPE_HEADER_VALUE:

                $part['value'] = $this->decodeHeader($part['value'], $this->current_header);

                if ($this->current_header == 'content-type') {
                    $info = $this->parseHeader($part['value'], $this->current_header);
                    $this->part['type'] = strtolower(strtok($info['value'], '/'));
                    $this->part['subtype'] = strtolower(strtok(''));
                    $this->part['params'] = $info['params'];
                    unset($info);
                }
                if ($this->current_header === 'from ') {
                    $this->current_header = 'from';
                    unset($this->part['headers']['from ']);
                    $this->part['headers']['from'] = strtok($part['value'], ' ');
                    $this->part['headers']['date'] = strtok('');
                } elseif (strpos($part['value'], "\n") === false) {
                    if ($part['value'] || !isset($this->part['headers'][$this->current_header])) {
                        if ($this->current_header == 'content-transfer-encoding') {
                            $part['value'] = strtolower($part['value']);
                        }
                        $this->part['headers'][$this->current_header] = $part['value'];
                    }
                } else {
                    $this->part['headers'][$this->current_header] = array(trim(strtok($part['value'], "\n")));
                    while (($value = strtok("\n")) !== false) {
                        $this->part['headers'][$this->current_header][] = ltrim(rtrim($value), "\t");
                    }
                }
                break;
            case self::TYPE_ATTACH:
                if ($this->options['max_attachments'] >= 0 && count($this->attachments) >= $this->options['max_attachments']) {
                    $this->state = self::STATE_END;
                    break;
                }
                $boundary = "\n--".$part['boundary'];
                if (!file_exists($this->options['attach_path'])) {
                    waFiles::create($this->options['attach_path']);
                }
                $path = $this->options['attach_path'].(count($this->attachments) + 1);
                if (isset($this->part['params']['name'])) {
                    if (($i = strrpos($this->part['params']['name'], '.')) !== false) {
                        $path .= substr($this->part['params']['name'], $i);
                    }
                } elseif ($this->part['type'] == 'image' && in_array($this->part['subtype'], array('gif', 'jpg', 'png'))) {
                    $path .= '.'.$this->part['subtype'];
                }
                $attach = array(
                    'file' => basename($path)
                );
                if (isset($this->part['params']['name'])) {
                    $attach['name'] = $this->part['params']['name'];
                }
                $attach['type'] = $this->part['type'];
                if (isset($this->part['subtype']) && $this->part['subtype']) {
                    $attach['type'] .= '/'.$this->part['subtype'];
                }
                if (isset($this->part['headers']['content-id'])) {
                    $attach['content-id'] = $this->part['headers']['content-id'];
                    if (substr($attach['content-id'], 0, 1) == '<') {
                        $attach['content-id'] = substr($attach['content-id'], 1);
                    }
                    if (substr($attach['content-id'], -1) == '>') {
                        $attach['content-id'] = substr($attach['content-id'], 0, -1);
                    }
                }
                $this->attachments[] = $attach;
                unset($attach);
                $fp = fopen($path, "w+");
                if (isset($this->part['headers']['content-transfer-encoding'])) {
                    if ($this->part['headers']['content-transfer-encoding'] == 'base64') {
                        stream_filter_append($fp, "convert.base64-decode", STREAM_FILTER_WRITE);
                    } elseif ($this->part['headers']['content-transfer-encoding'] == 'quoted-printable') {
                        stream_filter_append($fp, "convert.quoted-printable-decode", STREAM_FILTER_WRITE);
                    }
                }
                while (($i = strpos($this->buffer, $boundary, $this->buffer_offset)) === false && !$this->is_last) {
                    fwrite($fp, $this->buffer_offset ? substr($this->buffer, $this->buffer_offset) : $this->buffer);
                    $this->buffer = '';
                    $this->buffer_offset = 0;
                    $this->read();
                }
                // if last part
                if ($i === false) {
                    // try find incorrect boundary end
                    if (substr(rtrim($this->buffer, "\r\n"), -2) == '--') {
                        $j = strrpos(rtrim($this->buffer, "\r\n"), "\n");
                        $this->buffer = rtrim(substr($this->buffer, 0, $j), "\r\n");
                    }
                    // write part to attach file
                    fwrite($fp, substr($this->buffer, $this->buffer_offset));
                    $this->buffer = '';
                    $this->buffer_offset = 0;
                    $this->state = self::STATE_END;
                } else {
                    fwrite($fp, substr($this->buffer, $this->buffer_offset, $i - $this->buffer_offset));
                    $this->buffer_offset = $i;
                    $this->state = self::STATE_PART;
                }
                fclose($fp);
                if (!isset($this->part['headers']['content-disposition'])) {
                    $this->body[$this->part['type']."/".$this->part['subtype']] = file_get_contents($path);
                }
                if (isset($this->part['parent'])) {
                    $this->part_index = $this->part['parent'];
                    $this->part = &$this->parts[$this->part['parent']];
                }
                break;
            case self::TYPE_PART:
                switch ($this->part['type']) {
                    case 'text':
                        $this->part['data'] = $part['value'];
                        unset($part);

                        if (isset($this->part['headers']['content-transfer-encoding'])) {
                            switch ($this->part['headers']['content-transfer-encoding']) {
                                case 'base64':
                                    $this->part['data'] = base64_decode($this->part['data']);
                                    break;
                                case 'quoted-printable':
                                    $this->part['data'] = preg_replace("/=\r?\n/", '', $this->part['data']);
                                    $this->part['data'] = preg_replace_callback('/=([a-f0-9]{2})/i', array(__CLASS__, 'quotedPrintableReplace'), $this->part['data']);
                                    break;
                            }
                        }
                        if (isset($this->part['params']['charset']) && $this->part['params']['charset']) {
                            $this->part['params']['charset'] = preg_replace("/^[=\"]+/i", "", $this->part['params']['charset']);
                            if (strtolower($this->part['params']['charset']) != 'utf-8') {
                                $this->part['data'] = @iconv($this->part['params']['charset'], "utf-8//IGNORE", $this->part['data']);
                            }
                        } else {
                            $charset = mb_detect_encoding($this->part['data']);
                            if ($charset && strtolower($charset) != "utf-8" && $temp = @iconv($charset, 'UTF-8', $this->part['data'])) {
                                $this->part['data'] = $temp;
                                unset($temp);
                            } elseif (!preg_match("//u", $this->part['data'])) {
                                if (!$charset) {
                                    $temp = iconv("windows-1251", "utf-8//IGNORE", $this->part['data']);
                                    if (preg_match("/[а-я]/ui", $temp)) {
                                        $this->part['data'] = $temp;
                                    }
                                } else {
                                    if ($temp = @iconv('utf-8', 'utf-8//IGNORE', $this->part['data'])) {
                                        $this->part['data'] = $temp;
                                    }
                                }
                                unset($temp);
                            }
                        }
                        if (!isset($this->body[$this->part['type'].'/'.$this->part['subtype']])) {
                            $this->body[$this->part['type'].'/'.$this->part['subtype']] = $this->part['data'];
                        }
                        break;
                    default:
                        $this->part['data'] = $part['value'];
                        unset($part);
                }
                if (isset($this->part['parent'])) {
                    $this->part_index = $this->part['parent'];
                    $this->part = &$this->parts[$this->part['parent']];
                }
                $this->clearBuffer();
        }
    }

    protected function clearBuffer()
    {
        $this->buffer = substr($this->buffer, $this->buffer_offset);
        $this->buffer_offset = 0;
    }

    /**
     * Decode header value
     *
     * @param string|array $value
     * @param string $header
     * @param bool $decode_once - not decode if already decode this header
     *      Cause if call iconv several times on the same string it would be incorrect broken string as a final result
     * @return string
     */
    protected function decodeHeader($value, $header, $decode_once = false)
    {
        $this->decoded_header_counter[$header] = isset($this->decoded_header_counter[$header]) ? (int)$this->decoded_header_counter[$header] : 0;
        if ($decode_once && $this->decoded_header_counter[$header] >= 1) {
            return $value;
        }
        $value = $this->decodeHeaderValue($value);
        $this->decoded_header_counter[$header]++;
        return $value;
    }

    /**
     * Decode header value
     * @param string|array $value
     * @return string
     */
    protected function decodeHeaderValue($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->decodeHeaderValue($v);
            }
            unset($v);

            return $value;
        }

        if (preg_match("/=\?(.+)\?(B|Q)\?(.*)\?=?(.*)/i", $value, $m)) {
            $value = ltrim($value);
            $value = str_replace("\r", "", $value);
            if (isset($m[3]) && strpos($m[3], '_') !== false && strpos($m[3], ' ') === false) {
                $value = iconv_mime_decode(str_replace("\n", "", $value), 0, 'UTF-8');
            } else {
                $temp = mb_decode_mimeheader($value);
                if ($temp === $value) {
                    $value = iconv_mime_decode($value, 0, 'UTF-8');
                } else {
                    $value = $temp;
                }
            }
        } elseif (isset($this->part['params']['charset'])) {
            $value = iconv($this->part['params']['charset'], 'UTF-8//IGNORE', $value);
        }
        if (!preg_match('//u', $value)) {
            $charset = mb_detect_encoding($value);
            if ($charset && $temp = @iconv($charset, 'UTF-8', $value)) {
                $value = $temp;
            }
        }

        $value = str_replace(self::TEMP_NEW_LINE, "\n", $value);

        return $value;
    }

    /**
     * @param $str
     * @param string $header
     * @return array
     */
    protected function parseHeader($str, $header = null)
    {
        if (is_array($str)) {
            $str = implode("", $str);
        }

        $result = array('value' => trim(strtok($str, ';')), 'params' => array());
        while (($param = strtok('=')) !== false) {
            $result['params'][strtolower(trim($param))] = trim(strtok(';'), ' "');
        }

        if (isset($result['params']['name'])) {
            $result['params']['name'] = $this->decodeHeader($result['params']['name'], $header);
        }
        $temp = array();
        foreach ($result['params'] as $key => $value) {
            if (preg_match("/^([a-z]+)\*([0-9]*)\*?$/", $key, $match)) {
                if (!isset($match[2])) {
                    $match[2] = 0;
                }
                if (strpos($value, "''") !== false) {
                    $temp_v = explode("''", $value);
                    $value = urldecode($temp_v[1]);
                    if (strtolower($temp_v[0]) != 'utf-8') {
                        $value = iconv($temp_v[0], 'UTF-8//IGNORE', $value);
                    }
                }
                $temp[$match[1]][$match[2]] = $value;
                unset($result['params'][$key]);
            }
        }
        foreach ($temp as $key => $values) {
            $result['params'][$key] = implode("", $values);
        }
        return $result;
    }

}
