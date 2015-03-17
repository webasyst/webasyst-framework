<?php

if (!class_exists('Emogrifier')) {
    include_once(dirname(__FILE__).'/emogrifier/emogrifier.php');
}

/**
 * Wrapper around Emogrifier to allow waAutoload.
 * Emogrifier converts <style> blocks into inline styles.
 */
class mailerEmogrifier extends Emogrifier
{
    public function emogrify()
    {
        if (!class_exists('DOMDocument') || !class_exists('DOMXPath')) {
            return $this->html;
        }

        $result = parent::emogrify();

        // Undo urlencode of smarty tags that Emogrifier does
        // !!! Emogrifier breaks more than just {$}. Should probably dig deeper into it.
        $result = str_replace(array('%7B', '%24', '%7D'), array('{', '$', '}'), $result);
        return $result;
    }
}

