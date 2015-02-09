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
class waMailAddress
{
        
    public static function parseList($addresses)
    {
        $addresses = preg_replace('/\r?\n/', "\r\n", $addresses);
        $addresses = preg_replace('/\r\n(\t| )+/', ' ', $addresses);
        
        $result = array();
        while ($address = self::splitAddresses($addresses)) {
            $result[] = self::parse($address); 
        }
        return $result;
    }
    
    protected static function parse($address) 
    {
        // A couple of defaults.
        $phrase  = '';
        $comment = '';
        $comments = array();
    
        // Catch any RFC822 comments and store them separately.
        $temp = $address;
        while (strlen(trim($temp)) > 0) {
            $parts = explode('(', $temp);
            $before_comment = self::splitCheck($parts, '(');
            if ($before_comment != $temp) {
                // First char should be a (.
                $comment    = substr(str_replace($before_comment, '', $temp), 1);
                $parts      = explode(')', $comment);
                $comment    = self::splitCheck($parts, ')');
                $comments[] = $comment;
    
                // +2 is for the brackets
                $temp = substr($temp, strpos($temp, '('.$comment)+strlen($comment)+2);
            } else {
                break;
            }
        }
    
        foreach ($comments as $comment) {
            $address = str_replace("($comment)", '', $address);
        }
    
        $address = trim($address);
    
        // Check for name + route-addr
        if (substr($address, -1) == '>' && substr($address, 0, 1) != '<') {
            $parts = explode('<', $address);
            $name = self::splitCheck($parts, '<');
    
            $phrase = trim($name, ' "');
            $address = trim(substr($address, strlen($name.'<'), -1));

            // Only got addr-spec
        } else {
            // First snip angle brackets if present.
            if (substr($address, 0, 1) == '<' && substr($address, -1) == '>') {
                $address = substr($address, 1, -1);
            }
        }

        return array(
            'display' => $phrase,
            'address' => $address,
            'is_group' => false
        );
    }
    
    protected static function splitAddresses(&$addresses) 
    {
        if (false) {//self::isGroup($addresses)) {
            $split_char = ';';
            $is_group   = true;
        } else {
            $split_char = ',';
            $is_group   = false;
        }
        
        // Split the string based on the above ten or so lines.
        $parts  = explode($split_char, $addresses);
        $string = self::splitCheck($parts, $split_char);
        
        $addresses =  trim(substr($addresses, strlen($string) + 1));
        return $string;
    }
    
    protected static function splitCheck($parts, $char)
    {
        $string = $parts[0];
    
        for ($i = 0; $i < count($parts); $i++) {
            if (self::hasUnclosedQuotes($string) || self::hasUnclosedBrackets($string, '<>') || self::hasUnclosedBrackets($string, '[]') || self::hasUnclosedBrackets($string, '()')
            || substr($string, -1) == '\\') {
                if (isset($parts[$i + 1])) {
                    $string = $string . $char . $parts[$i + 1];
                } else {
                    throw new waException('Invalid address spec. Unclosed bracket or quotes');
                }
            } else {
                break;
            }
        }
    
        return $string;
    }    
    
    protected static function hasUnclosedQuotes($string)
    {
        $string = trim($string);
        $iMax = strlen($string);
        $in_quote = false;
        $i = $slashes = 0;
    
        for (; $i < $iMax; ++$i) {
            switch ($string[$i]) {
                case '\\':
                    ++$slashes;
                    break;
    
                case '"':
                    if ($slashes % 2 == 0) {
                        $in_quote = !$in_quote;
                    }
                    // Fall through to default action below.
    
                default:
                    $slashes = 0;
                break;
            }
        }
    
        return $in_quote;
    }    
    
    protected static function hasUnclosedBrackets($string, $chars)
    {
        $num_angle_start = substr_count($string, $chars[0]);
        $num_angle_end   = substr_count($string, $chars[1]);
    
        self::hasUnclosedBracketsSub($string, $num_angle_start, $chars[0]);
        self::hasUnclosedBracketsSub($string, $num_angle_end, $chars[1]);
    
        if ($num_angle_start < $num_angle_end) {
            throw new waException('Invalid address spec. Unmatched quote or bracket (' . $chars . ')');
        } else {
            return ($num_angle_start > $num_angle_end);
        }
    }    
    
    
    protected static function hasUnclosedBracketsSub($string, &$num, $char)
    {
        $parts = explode($char, $string);
        for ($i = 0; $i < count($parts); $i++){
            if (substr($parts[$i], -1) == '\\' || self::hasUnclosedQuotes($parts[$i]))
            $num--;
            if (isset($parts[$i + 1]))
            $parts[$i + 1] = $parts[$i] . $char . $parts[$i + 1];
        }
    
        return $num;
    }    
    
}
