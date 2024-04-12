<?php
/**
 * @since 3.0.0
 */
class waHtmlSanitizer
{
    /** @var array */
    protected $options;

    /** @var string */
    protected $attr_end;

    /** @var string */
    protected $attr_start;

    public function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
     * Sanitize content
     * @param string $content
     * @return mixed|string
     */
    public function sanitize($content)
    {
        // Make sure it's a valid UTF-8 string
        $content = preg_replace('~\\xED[\\xA0-\\xBF][\\x80-\\xBF]~', '?', mb_convert_encoding((string) $content, 'UTF-8', 'UTF-8'));

        // Remove all tags except known.
        // We don't rely on this for protection. Everything should be escaped anyway.
        // strip_tags() is here so that unknown tags do not show as escaped sequences, making the text unreadable.
        $allowable_tags = '<a><b><i><u><img><pre><mark><code><blockquote><p><strong><section><em><del><strike><span><ul><ol><li><div><font><br><table><thead><tbody><tfoot><tr><td><th><hr><h1><h2><h3><h4><h5><h6><style>';
        $content = strip_tags($content, $allowable_tags);

        // Strip <style>...</style>
        $content = $this->stripTagWithContent($content, 'style');

        // Replace all &entities; with UTF8 chars, except for &, <, >.
        $content = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $content);
        $content = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $content);
        $content = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $content);
        $content = html_entity_decode($content, ENT_COMPAT, 'UTF-8');

        // Remove redactor data-attribute
        $content = preg_replace('/(<[^>]+)data-redactor[^\s>]+/uis', '$1', $content);

        // Encode everything that looks unsafe.
        $content = htmlentities($content, ENT_QUOTES, 'UTF-8');

        //
        // The plan is: to quote everything, then unquote parts that seem safe.
        //

        // A trick we use to make sure there are no tags inside attributes of other tags.
        do {
            $this->attr_start = $attr_start = uniqid('<ATTRSTART').'>';
            $this->attr_end = $attr_end = uniqid('<ATTREND').'>';
        } while (strpos($content, $attr_start) !== false || strpos($content, $attr_end) !== false);

        // <a href="...">
        $content = preg_replace_callback(
            '~
                &lt;
                    a
                    \s+
                    href=&quot;
                        ([^"><]+?)
                    &quot;
                    (.*?)
                &gt;
            ~iuxs',
            array($this, 'sanitizeHtmlAHref'),
            $content
        );

        // <img src="...">
        $content = preg_replace_callback(
            '~
                &lt;
                    img\s+
                    .*?
                    src=&quot;
                        ([^"><]+?)
                    &quot;
                    .*?
                    /?
                &gt;
            ~iuxs',
            array($this, 'sanitizeHtmlImg'),
            $content
        );

        // Simple tags: <b>, <i>, <u>, <pre>, <blockquote> and closing counterparts.
        // All attributes are removed.
        $content = preg_replace(
            '~
                &lt;
                    (/?(?:a|b|i|u|pre|blockquote|p|strong|section|em|del|strike|span|ul|ol|li|div|font|br|table|thead|tbody|tfoot|tr|td|th|hr|h1|h2|h3|h4|h5|h6)/?)
                    ((?!&gt;)[^a-z\-\_]((?!&gt;)(\s|.))+)?
                &gt;
            ~iux',
            '<\1>',
            $content
        );

        // Remove $attr_start and $attr_end from legal attributes
        $content = preg_replace(
            '~
                '.preg_quote($attr_start).'
                ([^"><]*)
                '.preg_quote($attr_end).'
            ~ux',
            '\1',
            $content
        );

        // Remove illegal attributes, i.e. those where $attr_start and $attr_end are still present
        $content = preg_replace(
            '~
                '.preg_quote($attr_start).'
                .*
                '.preg_quote($attr_end).'
            ~uxUs',
            '',
            $content
        );
        $content = str_replace('&amp;', '&', $content);

        // Being paranoid... remove $attr_start and $attr_end if still present anywhere.
        // Should not ever happen.
        $content = str_replace(array($attr_start, $attr_end), '', $content);

        // Remove \n around <blockquote> startting and ending tags
        $content = preg_replace('~(?U:\n\s*){0,2}<(/?blockquote)>(?U:\s*\n){0,2}~i', '<\1>', $content);

        if (!empty($this->options['close_broken_tags'])) {
            $content = $this->closeBrokenTags($content);
        }

        return $content;
    }

    // Helper for sanitizeHtml()
    protected function sanitizeHtmlAHref($m)
    {
        $url = $this->sanitizeUrl(ifset($m[1]));
        return '<a href="'.$this->attr_start.$url.$this->attr_end.'" target="_blank" rel="nofollow">';
    }

    protected function sanitizeHtmlImg($m)
    {
        $url = $this->sanitizeUrl(ifset($m[1]));
        if (!$url) {
            return '';
        }

        $attributes = array(
            'src' => $url,
        );

        $legal_attributes = array(
            'width',
            'height'
        );

        foreach ($legal_attributes as $attribute) {
            preg_match(
                '~
                &lt;
                    img\s+
                    .*?
                    '.$attribute.'=&quot;([^"\'><]+?)&quot;
                    .*?
                    /?
                &gt;
            ~iuxs',
                $m[0],
                $match
            );

            if ($match) {
                $val = $match[1];

                // Additional check for positive integer attributes
                if (in_array($attribute, array('width', 'height'))) {
                    $val = (int) $val;
                    if ($val <= 0) {
                        continue;
                    }
                }

                $attributes[$attribute] = $val;
            }
        }

        foreach ($attributes as $attribute => $val) {
            $attributes[$attribute] = $attribute.'="'.$this->attr_start.$val.$this->attr_end.'"';
        }

        return '<img ' . join(' ', $attributes) . '>';
    }

    protected function sanitizeUrl($url)
    {
        if (empty($url)) {
            return '';
        }
        $url_alphanumeric = preg_replace('~&amp;[^;]+;~i', '', $url);
        $url_alphanumeric = preg_replace('~[^a-z0-9:]~i', '', $url_alphanumeric);
        if (preg_match('~^(javascript|vbscript):~i', $url_alphanumeric)) {
            return '';
        }

        static $url_validator = null;
        if (!$url_validator) {
            $url_validator = new waUrlValidator();
        }

        if (!$url_validator->isValid($url)) {
            $url = 'http://'.preg_replace('~^([^:]+:)?(//|\\\\\\\\)~', '', $url);
        }

        return $url;
    }

    protected function stripTagWithContent($text, $tag_name)
    {
        $opened_tag = '<(?:\s*?)' . $tag_name . '(?:\s+(?:.*?)>|>)';
        $closed_tag = '<(?:\s*?)/(?:\s*?)' . $tag_name . '(?:\s+(?:.*?)>|>)';
        $inner_content = '(.*?)';
        $pattern = '~' . $opened_tag . $inner_content . $closed_tag . '~iuxsm';
        $text = preg_replace($pattern, '', $text);
        return $text;
    }

    public function toPlainText($content)
    {
        // Make sure it's a valid UTF-8 string
        $content = preg_replace('~\\xED[\\xA0-\\xBF][\\x80-\\xBF]~', '?', mb_convert_encoding((string) $content, 'UTF-8', 'UTF-8'));

        // Strip <style>...</style>
        $content = $this->stripTagWithContent($content, 'style');
        // Strip <script>...</script>
        $content = $this->stripTagWithContent($content, 'script');

        // Remove all tags except some of them.
        $allowable_tags = '<pre><blockquote><p><section><li><div><br><tr><h1><h2><h3><h4><h5><h6>';
        $content = strip_tags($content, $allowable_tags);

        // Replace br to nl
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('~(<[^/])~', " \r\n$1", $content);
        $content = preg_replace('/^\s+/', '', $content);

        // Strip all remaining tags
        $content = strip_tags($content, '<unexisted>');
        $content = html_entity_decode($content);
        return $content;
    }

    protected function closeBrokenTags($string) {
        preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $string, $result);
        $opened_tags = $result[1];

        preg_match_all('#</([a-z]+)>#iU', $string, $result);
        $closed_tags = $result[1];

        if (count($closed_tags) == count($opened_tags)) {
            return $string;
        }

        // We have a mismatched opening and closing tags
        try {
            libxml_use_internal_errors(true);
            $string = mb_encode_numericentity(
                htmlspecialchars_decode(
                    htmlentities($string, ENT_NOQUOTES, 'UTF-8', false),
                    ENT_NOQUOTES
                ),
                [0x80, 0x10FFFF, 0, ~0],
                'UTF-8'
            );
            $dom = new DOMDocument( "1.0", "UTF-8" );
            $dom->encoding = 'UTF-8';
            $dom->loadHTML($string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            return $dom->saveHTML();
        } catch (Exception $e) {
            $log_record = join(PHP_EOL, [
                'Exception',
                $e->getMessage(),
                $e->getTraceAsString()
            ]);
            waLog::log($log_record, 'wa-sanitizer-error.log');
            return $string;
        }
    }
}
