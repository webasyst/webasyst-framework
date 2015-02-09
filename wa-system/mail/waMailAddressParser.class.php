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
class waMailAddressParser
{
    protected $offset;
    protected $n;
    protected $string;
    protected $state;
    protected $data;
    protected $buffer_name = "";
    protected $buffer = "";
    protected $group;
    
    protected $expected;
    
    const STATE_START = 0;
    const STATE_ADDRESS = 1;
    const STATE_NAME = 2;    
    const STATE_EMAIL = 3;
    const STATE_GROUP_NAME = 4;
    
    public function __construct($string)
    {
        $this->n = strlen($string);
        $this->string = $string;
        $this->offset = 0;
    }
    
    public function parse()
    {
        if ($this->data === null) {
            $this->data = array();
            $this->state = self::STATE_START;
            while ($this->offset < $this->n) {
                $this->parseAddress();
            }
        }
        return $this->data;
    }
    
    protected function parseAddress()
    {
        switch ($this->state) {
            case self::STATE_START:
                $this->skip();
                $c = $this->string[$this->offset];
                if ($c == '"' || $c == "'") {
                    $this->expected = $c;
                    $this->offset++;
                    $this->buffer = "";
                    $this->state = self::STATE_NAME;
                } elseif ($c == '<') {
                    $this->offset++;
                    $this->expected = '>';
                    $this->state = self::STATE_EMAIL;
                } elseif ($c == ',' || $c == ';') {
                    $this->offset++;
                } else {
                    $this->state = self::STATE_ADDRESS;
                }
                break;
            case self::STATE_GROUP_NAME:
                $i = strpos($this->string, ':', $this->offset);
                if ($i === false) {
                    throw new waException(sprintf("Expected :"));
                }
                $this->buffer.= substr($this->string, $this->offset, $i - $this->offset);
                $this->group = $this->buffer;
                $this->buffer = "";
                $this->offset = $i + 1;
                $this->skip();
                $this->state = self::STATE_ADDRESS;
                break;
            case self::STATE_NAME:
                // find close quote
                $i = strpos($this->string, $this->expected, $this->offset);
                if ($i === false) {
                    $i = strpos($this->string, ':', $this->offset);
                    if ($i === false) {
                        throw new waException(sprintf("Expected %s", $this->expected));
                    } else {
                        $this->state = self::STATE_GROUP_NAME;
                        break;
                    }
                }
                // quote escaped
                if ($this->expected != '<' && $this->string[$i - 1] == "\\") {
                    $this->buffer .= substr($this->string, $this->offset, $i - $this->offset - 1).$this->expected;
                    $this->offset = $i + 1;
                } else {
                    $this->buffer .= substr($this->string, $this->offset, $i - $this->offset);
                    if ($this->expected == '<') {
                        $this->offset = $i;
                    } else {
                        $this->offset = $i + 1;
                        $this->expected = "";
                        $this->skip();
                    }
                    if ($this->offset < $this->n && $this->string[$this->offset] == '<') {
                        $this->offset++;
                        $this->expected = '>';
                        $this->buffer_name = str_replace(array("\r\n", "\n", "\t", "  "), " ", trim($this->buffer));
                        $this->buffer = "";
                        $this->state = self::STATE_EMAIL;
                    } else {
                        throw new waException("Email not found");
                    }
                }
                break;
            case self::STATE_ADDRESS:
                $c = $this->string[$this->offset];
                if ($c == '@') {
                    $this->state = self::STATE_EMAIL;
                } elseif ($c == ' ' || $c == "\t" || $c == "\n" || $c == "\r") {
                    $this->state = self::STATE_NAME;
                    $this->expected = '<';
                } else {
                    $this->buffer .= $c;
                    $this->offset++;
                }
                break;
            case self::STATE_EMAIL:
                // if expected symbol "<"
                if ($this->expected) {
                    $i = strpos($this->string, $this->expected, $this->offset);
                    if ($i === false) {
                        throw new waException("Bracket '<' not closed");
                    }
                    $this->buffer = trim(substr($this->string, $this->offset, $i - $this->offset));
                    $this->offset = $i + 1;
                } else {
                    // find end of the email
                    $symbols = array(" ", "\n", "\t", "\r", ",", ";");
                    while ($this->offset < $this->n && !in_array($this->string[$this->offset], $symbols)) {
                        $this->buffer .= $this->string[$this->offset];
                        $this->offset++;
                    }
                }
                $this->skip();
                $address = array(
                    'name' => $this->buffer_name,
                    'email' => $this->buffer
                );
                // comment
                if ($this->offset < $this->n && $this->string[$this->offset] == '(') {
                    $i = strpos($this->string, ')', $this->offset);
                    if ($i == false) {
                        throw new waException("Unclosed comment, expected )");
                    } else {
                        $address['comment'] = trim(substr($this->string, $this->offset + 1, $i - $this->offset - 1));
                        $this->offset = $i + 1;
                    }
                }
                $this->data[] = $address; 
                $this->buffer = $this->buffer_name = "";
                $this->expected = "";
                $this->state = self::STATE_START;
                break;
        }
    }
    
    
    protected function skip() 
    {
        $symbols = array(" ", "\t", "\r", "\n");
        while ($this->offset < $this->n && in_array($this->string[$this->offset], $symbols)) {
            $this->offset++;
        }
    }
}

