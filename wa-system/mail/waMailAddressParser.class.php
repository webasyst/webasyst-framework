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
            $this->parseAddress();
        }
        return $this->data;
    }

    protected function parseAddress()
    {
        $pattern = '~^[^\s@]+@[^\s@]+\.[^\s@\.]{2,}$~u';    // here is {2,} after '.' - supports international domains in punycode format
        $addrss = preg_split("~(,\s|\n)~", $this->string);
        foreach ($addrss as $a) {
            $name = $email = false;
            if (preg_match('~<([^>]+)>[,;]?$~', $a, $m) && preg_match($pattern, trim($m[1]))) {
                $name = trim(str_replace($m[0], '', $a), "'\" \t\n\r");
                $email = trim($m[1]);
            } elseif (preg_match($pattern, trim($a))) {
                $name = '';
                $email = trim($a);
            }

            if ($email) {
                $this->data[] = array(
                    'name'  => $name,
                    'email' => $email,
                );
            }
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
