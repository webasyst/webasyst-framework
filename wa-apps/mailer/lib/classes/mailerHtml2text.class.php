<?php

/**
 * Helper to build plain-text version of a message from HTML version.
 */
class mailerHtml2text
{

    /**
     * Convert HTML to plain text to use in emails.
     * @param string $html
     * @return string
     */
    public static function convert($html)
    {
        // Remove HTML tags from string.
        $pre_converted = null;
        if (class_exists('DOMDocument')) {
            // DOM-based converter tends to produce better results
            // but sometimes fails due to HTML syntax errors in string.
            $doc = new DOMDocument();
            if (@$doc->loadHTML('<!DOCTYPE html><html><head><META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8"></head><body>'.preg_replace("~\s+~imu", " ", $html)).'</body></html>') {
                // Use DOM-based converter
                $pre_converted = self::convertNode($doc);
            }
        }
        if ($pre_converted === null) {
            // Fall back to simple approach when DOMDocument failed
            $pre_converted = strip_tags($html);
        }

        // Remove leading and trailig whitespace on each line.
        $result = '';
        foreach(explode("\n", trim($pre_converted)) as $line) {
            $result .= trim($line)."\n";
        }

        // Replace excess newlines from a string
        $result = preg_replace('~\n{3,}~u', "\n\n", trim($result));
        return $result;
    }

    /**
     * Convert single node into plain text (recursively).
     * @param object $node
     * @return string
     */
    protected static function convertNode($node)
    {
        // Simple cases: text node and doctype declaration
        if ($node instanceof DOMText) {
            return $node->wholeText;
        }
        if ($node instanceof DOMDocumentType) {
            return "";
        }

        $tag_name = strtolower($node->nodeName);
        $output = "";

        // Add leading whitespace depending on tag name.
        // For simple tags return the result immidiately.
        switch ($tag_name) {
                case "hr":
                    return "------\n";

                case "style":
                case "head":
                case "title":
                case "meta":
                case "script":
                    return "";

                case "li":
                    if (!empty($node->li_symbol)) {
                        $output .= "\n".$node->li_symbol." ";
                    } else {
                        $output .= "\n* ";
                    }
                    break;

                case "ul":
                case "ol":
                case "p":
                case "div":
                case "br":
                case "h1":
                case "h2":
                case "h3":
                case "h4":
                case "h5":
                case "h6":
                    $output .= "\n\n";
                    break;
        }

        // Convert subnodes recursively
        if ($tag_name == 'ol') {
            $li_count = 0;
        }
        if (!empty($node->childNodes)) {
            for ($i = 0; $i < $node->childNodes->length; $i++) {
                $item = $node->childNodes->item($i);
                if ($tag_name == 'ol' && !($item instanceof DOMText) && strtolower($item->nodeName) == 'li') {
                    $li_count++;
                    $item->li_symbol = $li_count.'.';
                }
                $output .= self::convertNode($item);
            }
        }

        // Trailing whitespace
        switch ($tag_name) {
            case "p":
            case "div":
            case "br":
            case "h1":
            case "h2":
            case "h3":
            case "h4":
            case "h5":
            case "h6":
            case "tr":
            case "td":
                $output .= "\n\n";
                break;

            // Links are represented in plain-text as 'text ( link )'
            case "a":
                $href = $node->getAttribute("href");
                if ($href) {
                    if ($href !== $output) {
                        // replace it
                        $output .= " ( $href )";
                    }
                }
                break;

            default:
                break; // Nothing to do
        }

        return $output;
    }
}

