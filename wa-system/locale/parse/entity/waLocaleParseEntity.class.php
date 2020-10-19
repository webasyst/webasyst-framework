<?php

abstract class waLocaleParseEntity implements waLocaleParseEntityInterface
{
    const WEBASYST_SYSTEM_PATTERN = '_ws';

    const WEBASYST_DEFAULT_PATTERN = '_w';

    const WEBASYST_PLUGIN_PATTERN = '_wp';

    const WEBASYST_DOMAIN_PATTERN = '_wd';

    const WEBASYST_SYSTEM_PLUGIN_PATTERN = '->_w';

    const OPEN_SPRINTF_PATTERN = 'sprintf_wp';

    /**
     * List of webasyst patterns by which to search for translations
     * @return string[]
     */
    abstract public function getWebasystFunctionPatterns();

    /**
     * List of patterns by which to search for translations
     * @return string[]
     */
    abstract public function getOpenFunctionPatterns();

    /**
     * List of files to look for translations in
     * @return string[]
     */
    abstract public function getSources();

    /**
     * @return array|mixed
     * @throws waException
     */
    public function getLocales()
    {
        $locales_path = wa()->getConfig()->getRootPath()."/wa-config/locale.php";
        if (file_exists($locales_path)) {
            $locales = include($locales_path);
        } else {
            $locales = [
                'en_US',
                'ru_RU',
            ];
        }

        return $locales;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        $sources = $this->getSources();

        $result = [];
        foreach ($sources as $source) {
            $result = array_merge($result, $this->parseFolders($source));
        }

        return $result;
    }

    /**
     * Recursively in a folder, searches for files by the desired extension.
     * Extensions are stored in $this->config['include']
     *
     * @param $dir
     * @param string $context
     * @return array
     */
    public function parseFolders($dir, $context = "/")
    {
        $result = [];
        if (!file_exists($dir)) {
            return $result;
        }

        $handler = opendir($dir);
        while ($file = readdir($handler)) {
            // ignore parent and current paths
            if ($file == '.' || $file == '..') {
                continue;
            }

            if (is_dir($dir."/".$file)) {
                $folder = $dir."/".$file;
                $new_context = $context.$file."/";
                $result = array_merge($result, $this->parseFolders($folder, $new_context));
            } elseif (preg_match($this->getFilesExtPattern(), $file)) {
                $result[] = $dir.'/'.$file;
            }

        }
        return $result;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        $files = $this->getFiles();

        $result = [];
        $pattern = $this->getBacktickPattern();

        foreach ($files as $file) {
            $text = file_get_contents($file);
            $path = $this->getRelativePath($file);

            $result = $this->mergeResult($result, $this->getByBacktick($pattern, $text, $path));
            $result = $this->mergeResult($result, $this->getByFunctions($text, $path));
        }

        return $result;
    }

    /**
     * @param $messages
     * @param $locale
     * @return bool
     */
    public function preSave(&$messages, $locale)
    {
       return true;
    }

    /**
     * @return bool|null|string
     * @throws waException
     */
    protected function getRootPath()
    {
        return wa()->getConfig()->getRootPath();
    }

    /**
     * The line in which the transfer is located
     * @param $text
     * @param $position
     * @return int
     */
    protected function getLine($text, $position)
    {
        $line = explode("\n", mb_substr($text, 0, $position));
        return count($line);
    }

    /**
     * @param $file
     * @return bool|string
     */
    public function getRelativePath($file)
    {
        $full_path = realpath(dirname(__FILE__)."/../../../../");
        return substr($file, strlen($full_path));
    }

    /**
     * @param array $old_result
     * @param array $new_result
     * @return array
     */
    protected function mergeResult($old_result, $new_result)
    {
        $result = $old_result;

        foreach ($new_result as $msgid => $data) {
            $old_data = ifset($old_result, $msgid, []);
            $result[$msgid] = array_merge_recursive($data, $old_data);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getQuotes()
    {
        return ["'", '"'];
    }

    /**
     * @param $text
     * @param $path
     * @return array
     */
    protected function getByFunctions($text, $path)
    {
        $result = [];

        $webasyst_functions = $this->getWebasystFunctionPatterns();
        $domain_function = $this->getDomainFunctionPattern();
        $open_functions = $this->getOpenFunctionPatterns();

        foreach ($this->getQuotes() as $quote) {
            $word_pattern = $this->getWordPattern($quote);
            $app_pattern = $this->getAppPattern($quote, $this->getDomain());

            if ($webasyst_functions) {
                $plural_pattern = $this->getPluralPattern($webasyst_functions, $word_pattern);
                $result = $this->mergeResult($result, $this->getByPlural($plural_pattern, $text, $path, $quote));

                $default_pattern = $this->getDefaultPattern($webasyst_functions, $word_pattern);
                $result = $this->mergeResult($result, $this->getByDefault($default_pattern, $text, $path, $quote));
            }

            if ($domain_function) {
                $domain_plural_pattern = $this->getDomainPluralPattern($word_pattern, $app_pattern);
                $result = $this->mergeResult($result, $this->getByPlural($domain_plural_pattern, $text, $path, $quote));

                $domain_default_pattern = $this->getDomainDefaultPattern($word_pattern, $app_pattern);
                $result = $this->mergeResult($result, $this->getByDefault($domain_default_pattern, $text, $path, $quote));
            }

            if ($open_functions) {
                $open_pattern = $this->getOpenPattern($open_functions, $word_pattern);
                $result = $this->mergeResult($result, $this->getByOpen($open_pattern, $text, $path, $quote));
            }
        }

        return $result;
    }

    /**
     * @param $pattern
     * @param $text
     * @param $path
     * @return array
     */
    protected function getByBacktick($pattern, $text, $path)
    {
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        $result = [];

        $backtick_matches = ifset($matches, 1, []);
        if ($backtick_matches) {
            foreach ($backtick_matches as $backtick_match) {
                $word = ifset($backtick_match, 0, false);
                $position = ifset($backtick_match, 1, false);

                if ($word) {
                    $result[$word]['comments'][] = $path.":".$this->getLine($text, $position);
                }
            }
        }

        return $result;
    }

    /**
     * @param $pattern
     * @param $text
     * @param $path
     * @param $quote
     * @return array
     */
    protected function getByDefault($pattern, $text, $path, $quote)
    {
        $result = [];
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        $default_matches = ifset($matches, 2, false);

        if ($default_matches) {
            foreach ($default_matches as $default_match) {
                $word = ifset($default_match, 0, false);
                $position = ifset($default_match, 1, false);

                if ($word) {
                    $word = preg_replace("@\\\\{$quote}@", $quote, $word);
                    $result[$word]['comments'][] = $path.":".$this->getLine($text, $position);
                }
            }
        }

        return $result;
    }

    /**
     * @param $pattern
     * @param $text
     * @param $path
     * @param $quote
     * @return array
     */
    protected function getByPlural($pattern, $text, $path, $quote)
    {
        $result = [];
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        $first_form = ifset($matches, 1, []);
        $plurals = ifset($matches, 2, []);

        if ($first_form && $plurals) {
            foreach ($first_form as $match_index => $plural_match) {
                $word = ifset($plural_match, 0, false);
                $position = ifset($plural_match, 1, false);
                $plural = ifset($plurals, $match_index, 0, false);

                if ($word && $plural) {
                    $word = preg_replace("@\\\\{$quote}@", $quote, $word);
                    $plural = preg_replace("@\\\\{$quote}@", $quote, $plural);

                    $result[$word]['msgid_plural'] = $plural;
                    $result[$word]['comments'][] = $path.":".$this->getLine($text, $position);
                }
            }
        }

        return $result;
    }

    /**
     * @param $pattern
     * @param $text
     * @param $path
     * @param $quote
     * @return array
     */
    protected function getByOpen($pattern, $text, $path, $quote)
    {
        $result = [];
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        $open_form = ifset($matches, 2, []);
        if ($open_form) {
            foreach ($open_form as $open) {
                $word = ifset($open, 0, false);
                $position = ifset($open, 1, false);

                if ($word) {
                    $word = preg_replace("@\\\\{$quote}@", $quote, $word);
                    $result[$word]['comments'][] = $path.":".$this->getLine($text, $position);
                }
            }
        }

        return $result;
    }

    /**
     * Files to look for translations in
     * @return string
     */
    protected function getFilesExtPattern()
    {
        return "/^.+\\.(html|(?<!min\\.)js|php)$/ui";
    }

    /**
     * @return string
     */
    protected function getBacktickPattern()
    {
        // Lets you escape the back quote. `i\`m text`
        // Break the regular expression into pieces so that it does not find itself 0_0
        $pattern = "/\\[";
        $pattern .= "`((?:\\\\`|[^`])+?)";
        $pattern .= "`\\]/usi";

        return $pattern;
    }

    /**
     * @param $function_pattern
     * @param $word_pattern
     * @return string
     */
    protected function getDefaultPattern($function_pattern, $word_pattern)
    {
        $function_for_regex = implode('|', $function_pattern);

        $pattern = '@';

        // Find function name in files. Include this
        // Example: _w, _wp, etc
        $pattern .= "(?:($function_for_regex))";

        // Find commented function
        // Example: /*_wp 'Application ID',
        $pattern .= '(?:\\s*\\*/[\\r\\n]*)?\\s*';

        // Find text for translate
        // ('Application ID')
        $pattern .= '\\('.$word_pattern.'\\)';

        // Add regex flags
        $pattern .= '@mus';

        return $pattern;
    }

    /**
     * @param $functions
     * @param $word_pattern
     * @return string
     */
    protected function getPluralPattern($functions, $word_pattern)
    {
        $function_for_regex = implode('|', $functions);

        $plural_pattern = '@';

        // Find function name in files.
        // Example: _w, _wp, etc
        $plural_pattern .= "(?:$function_for_regex)";

        // Find commented function
        // Example: /*_wp*/('Application ID'),
        $plural_pattern .= '(?:\\s*\\*/[\\r\\n]*)?\\s*\\';

        // Find plural form text
        // Example ('Application ID', 'Application IDs', 1)
        $plural_pattern .= '('.$word_pattern.','.$word_pattern.'(,\\s*|[\\r\\n\\s]*\))';

        // Add regex flags
        $plural_pattern .= '@mus';

        return $plural_pattern;
    }

    /**
     * @param $functions
     * @param $word_pattern
     * @return string
     */
    protected function getOpenPattern($functions, $word_pattern)
    {
        $function_for_regex = implode('|', $functions);

        $pattern = '@';

        // Find function name in files. Include this
        // Example: _w, _wp, etc
        $pattern .= "(?:($function_for_regex))";

        // Find commented function
        // Example: /*_wp*/('Application ID'),
        $pattern .= '(?:\\s*\\*/[\\r\\n]*)?\\s*';

        // Todo i don't understand =((
        $pattern .= '\\('.$word_pattern.',';

        // Add regex flags
        $pattern .= '@mus';

        return $pattern;
    }

    /**
     * @param $quote
     * @return string
     */
    protected function getWordPattern($quote)
    {
        // Find carriage return, new line, invisible character
        $word_pattern = '[\\r\\n\\s]*';

        // Opening quotes
        $word_pattern .= $quote;

        // Find whitespace character
        $word_pattern .= '[\\s]*';

        // Ignore quotes in text
        // example: _w("Shop \"Hell\"")
        $word_pattern .= "((?:\\\\$quote|[^$quote\\r\\n])+?)";

        // Find whitespace character
        $word_pattern .= '[\\s]*';

        // Closed quotes
        $word_pattern .= $quote;

        // Find whitespace character
        $word_pattern .= '[\\s]*';

        return $word_pattern;
    }

    /**
     * @param $quote
     * @param string $app_id
     * @return string
     */
    protected function getAppPattern($quote, $app_id)
    {
        // Find carriage return, new line, invisible character
        $getAppPattern = '[\\r\\n\\s]*';

        // Opening quotes
        $getAppPattern .= $quote;

        // Exact match with application id
        $getAppPattern .= $app_id;

        // Closed quotes
        $getAppPattern .= $quote;

        // Find whitespace character
        $getAppPattern .= '[\\s]*';

        return $getAppPattern;
    }

    protected function getDomainDefaultPattern($word_pattern, $app_pattern)
    {
        $pattern = '@';

        // Find function name in files
        // Example: _wd
        $pattern .= '(?:(' . self::WEBASYST_DOMAIN_PATTERN . '))';

        // Find commented function
        // Example: /*_wd*/
        $pattern .= '(?:\\s*\\*/[\\r\\n]*)?\\s*';

        // Find text for translate
        // Example: ('shop', 'Product')
        $pattern .= '\\(' . $app_pattern . ',' . $word_pattern . '\\)';

        // Add regex flags
        $pattern .= '@mus';

        return $pattern;
    }

    protected function getDomainPluralPattern($word_pattern, $app_pattern)
    {
        $plural_pattern = '@';

        // Find function name in files
        // Example: _wd
        $plural_pattern .= '(?:' . self::WEBASYST_DOMAIN_PATTERN . ')';

        // Find commented function
        // Example: /*_wd*/
        $plural_pattern .= '(?:\\s*\\*/[\\r\\n]*)?\\s*\\';

        // Find plural form text
        // Example: ('shop', 'Product', 'Products', 1)
        $plural_pattern .= '(' . $app_pattern . ',' . $word_pattern . ',' . $word_pattern . '(,\\s*|[\\r\\n\\s]*\))';

        // Add regex flags
        $plural_pattern .= '@mus';

        return $plural_pattern;
    }

    /**
     * Domain pattern by which to search for translations
     * @return string
     */
    public function getDomainFunctionPattern()
    {
        return null;
    }
}