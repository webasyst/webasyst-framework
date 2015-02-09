<?php

/**
 *
 * @link http://www.webasyst.com/developers/docs/features/localization/
 *
 */

require_once dirname(__FILE__).'/waGettext.class.php';

class waGettextParser
{
    protected $config = array(
        'include' => '.*',
        'project' => 'WebAsyst',
        'path'    => '/',
        'locales' => array(),
        'debug'   => false,
        'verify'  => false,
    );

    protected $words = array();

    public function __construct($config)
    {
        foreach ($config as $name => $value) {
            $this->config[$name] = $value;
        }
    }

    public function getFiles($dir, $context = "/")
    {
        if (!file_exists($dir)) {
            return array();
        }
        $result = array();
        $handler = opendir($dir);
        while ($file = readdir($handler)) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($dir."/".$file)) {
                $result = array_merge($result, $this->getFiles($dir."/".$file, $context.$file."/"));
            } else {
                if (preg_match("/^".$this->config['include']."$/ui", $file)) {
                    $result[] = array($context, $file);
                }
            }
        }
        return $result;
    }

    public function getWords($file)
    {
        static $debug = false;
        $counter = 0;
        $text = file_get_contents($file);
        $file = substr($file, strlen(realpath(dirname(__FILE__)."/../../")));
        $matches = array();
        $pattern = "/\\[".($this->config['project'] == 'webasyst' ? "s?" : "")."`((?:\\\\`|[^`])+?)`\\]/usi";
        if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $word = $match[0];
                $counter += $this->cache(array($word), $file.":".$this->getLine($text, $match[1]));
            }
        }
        if ($this->config['debug'] && !$debug) {
            print "Template pattern:\n".$pattern."\n";
        }

        $function_pattern = array("\\\$_");
        if ($this->config['project'] == 'webasyst') {
            $function_pattern[] = '_ws';
        } elseif (strpos($this->config['project'], 'wa-plugins') !== false) {
            $function_pattern[] = '_wp';
            $function_pattern[] = '->_w';
        } elseif (strpos($this->config['project'], '/plugins/')) {
            $function_pattern[] = '_wp';
        } else {
            $function_pattern[] = '_w';
        }
        $commas = array('"', "'");
        $word_pattern = '\\s*%1$s\\s*((?:\\\\%s|[^%1$s\\r\\n])+?)\\s*%1$s\\s*';
        foreach ($commas as $comma) {

            $plural_pattern = '@(?:'.implode('|', $function_pattern).')(?:\\s*\\*/)?\\s*\\('.sprintf($word_pattern, $comma).','.sprintf($word_pattern, $comma).',\\s*@mus';
            if ($this->config['debug'] && !$debug) {
                print "Plural forms pattern:\n".$plural_pattern."\n";
            }
            #plural forms support
            if (preg_match_all($plural_pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $i => $match) {
                    $word = preg_replace("@\\\\{$comma}@", $comma, $match[0]);
                    $words = preg_replace("@\\\\{$comma}@", $comma, $matches[2][$i][0]);
                    $line = $this->getLine($text, $match[1]);
                    $counter += $this->cache(array($word, $words), $file.":".$line);
                }
            }
        }
        foreach ($commas as $comma) {
            $pattern = '@(?:'.implode('|', $function_pattern).')(?:\\s*\\*/)?\\s*\\('.sprintf($word_pattern, $comma).'\\)@mus';
            if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $word = preg_replace("@\\\\{$comma}@", $comma, $match[0]);
                    $counter += $this->cache(array($word), $file.":".$this->getLine($text, $match[1]));
                }
            }

            if ($this->config['debug'] && !$debug) {
                print "Single forms pattern:\n".$pattern."\n";
                $debug = true;
            }
        }

        return $counter;

    }

    protected function getLine($text, $pos)
    {
        $lines = explode("\n", mb_substr($text, 0, $pos));
        return count($lines);
    }

    public function cache($words_info, $line = null)
    {
        $new = 0;
        $msg_id = reset($words_info);
        if (!isset($this->words[$msg_id])) {
            $new = 1;
            $this->words[$msg_id] = array('lines' => array());
            if (isset($words_info[1])) {
                $this->words[$msg_id]['plural'] = $words_info[1];
            }
        }
        if (!empty($line)) {
            $this->words[$msg_id]['lines'][] = $line;
        }
        return $new;
    }


    public function exec($sources)
    {
        foreach ($sources as $source) {
            $files = $this->getFiles($source);
            foreach ($files as $file) {
                $count = $this->getWords($source.$file[0].$file[1]);
                if ($this->config['debug']) {
                    echo $file[0].$file[1].': '.$count."\r\n";
                }
            }
        }

        $this->save();
    }

    public function save()
    {
        $htaccess_path = $this->config['path']."/.htaccess";
        if (!file_exists($htaccess_path)) {
            if (!file_exists($this->config['path'])) {
                mkdir($this->config['path'], 0777, true);
            }
            file_put_contents($htaccess_path, "Deny from all\n");
        }
        foreach ($this->config['locales'] as $locale => $domain) {
            $locale_path = $this->config['path']."/".$locale."/"."LC_MESSAGES"."/".$domain.".po";
            if ($this->config['verify']) {
                $locale_path_log = $locale_path.'.log';
                if (file_exists($locale_path)) {
                    if ($fh = fopen($locale_path_log, "w")) {
                        $counter = 0;
                        flock($fh, LOCK_EX);
                        $gettext = new waGettext($locale_path, true);
                        $strings = $gettext->read();
                        $strings = $strings['messages'];
                        $words = $this->words;
                        foreach ($strings as $msg_id => $info) {
                            if (!isset($this->words[$msg_id])) {
                                fputs($fh, "msgid \"".str_replace('"', '\\"', $msg_id)."\"\n");
                                ++$counter;
                            } else {
                                unset($words[$msg_id]);
                            }
                        }

                        if ($counter) {
                            echo "\r\n{$counter} string(s) is out of date for {$locale} at {$domain}\r\n";
                        }
                        if ($words) {
                            fputs($fh, "\n\n#Missed:\n\n\n");
                            foreach ($words as $msg_id => $info) {
                                if (isset($strings[$msg_id])) {
                                    unset($words[$msg_id]);
                                    continue;
                                }
                                fputs($fh, "msgid \"".str_replace('"', '\\"', $msg_id)."\"\n");
                            }
                            $counter = count($words);
                            echo "\r\n{$counter} string(s) is missed or not translated for {$locale} at {$domain}\r\n";
                        }

                        fflush($fh);
                        flock($fh, LOCK_UN);
                        fclose($fh);

                    } else {
                        echo "\r\nError while open {$locale_path} in a+ mode\r\n";
                    }
                } else {
                    echo "\r\Locale file {$locale_path} is missed\r\n";
                }
            } else {

                if (!file_exists($locale_path)) {
                    $this->create($locale);
                    $strings = array();
                } else {
                    $gettext = new waGettext($locale_path, true);
                    $strings = $gettext->read();
                    $strings = $strings['messages'];
                }
                $counter = 0;
                if ($fh = fopen($locale_path, "a+")) {
                    if ($this->config['debug']) {
                        echo "\r\n".$locale_path." - ".count($this->words)." records\r\n";
                    }
                    flock($fh, LOCK_EX);
                    foreach ($this->words as $msg_id => $info) {
                        // Ищем вхождения текущей фразы
                        if (isset($strings[$msg_id])) {
                            continue;
                        }
                        // Если не нашли - записываем
                        // Если не нашли - записываем
                        foreach ((array)$info['lines'] as $line) {
                            fputs($fh, "\n#: ".$line);
                        }
                        fputs($fh, "\nmsgid \"".str_replace('"', '\\"', $msg_id)."\"\n");
                        if (!empty($info['plural'])) {
                            fputs($fh, "msgid_plural \"".str_replace('"', '\\"', $info['plural'])."\"\n");
                            $n = ($locale == 'ru_RU') ? 3 : 2;
                            for ($i = 0; $i < $n; $i++) {
                                fputs($fh, "msgstr[{$i}] \"\"\n");
                            }
                        } else {
                            fputs($fh, "msgstr \"\"\n");
                        }
                        ++$counter;
                    }
                    fflush($fh);
                    flock($fh, LOCK_UN);
                    fclose($fh);
                } else {
                    echo "\r\nError while open {$locale_path} in a+ mode\r\n";
                }
                if ($counter) {
                    echo "\r\nAdded {$counter} string(s) for locale {$locale} at {$domain}\r\n";
                }
            }
        }
    }


    public function create($locale)
    {
        $time = date("Y-m-d H:iO");
        if ($locale == 'ru_RU') {
            $plural = '
"Plural-Forms: nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));\n"';
        } else {
            $plural = '';
        }
        $text = <<<TEXT
msgid ""
msgstr ""
"Project-Id-Version: {$this->config['project']}\\n"
"POT-Creation-Date: {$time}\\n"
"PO-Revision-Date: \\n"
"Last-Translator:  {$this->config['project']}\\n"
"Language-Team:  {$this->config['project']}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=utf-8\\n"
"Content-Transfer-Encoding: 8bit\\n"{$plural}
"X-Poedit-Language: {$locale}\\n"
"X-Poedit-SourceCharset: utf-8\\n"
"X-Poedit-Basepath: .\\n"
"X-Poedit-SearchPath-0: .\\n"
"X-Poedit-SearchPath-1: .\\n"

TEXT;

        $locale_path = $this->config['path']."/".$locale."/"."LC_MESSAGES";
        if (!file_exists($locale_path)) {
            mkdir($locale_path, 0777, true);
        }
        $locale_file = $locale_path."/".$this->config['locales'][$locale].".po";
        $f = fopen($locale_file, "w+");
        if (!$f) {
            throw new Exception("Could not create locale: ".$locale_file);
        }
        fwrite($f, $text);
        fclose($f);
    }
}


// start script

if (count($argv) < 2) {
    die("Usage: php locale.php APP_ID[/plugins/PLUGIN_ID]\n");
}

@mb_internal_encoding('UTF-8');
@ini_set('default_charset', 'utf-8');

@ini_set('register_globals', 'off');
// magic quotes
@ini_set("magic_quotes_runtime", 0);
if (version_compare('5.4', PHP_VERSION, '>') && function_exists('set_magic_quotes_runtime') && get_magic_quotes_runtime()) {
    @set_magic_quotes_runtime(false);
}

$app_id = $argv[1];
$locale_id = $app_id;
if ($app_id == 'webasyst') {
    $path = realpath(dirname(__FILE__)."/../../")."/wa-system/";
    $include = array(
        substr($path, 0, -1)
    );
} elseif (strpos($app_id, 'wa-plugins/') === 0) {

    $path = realpath(dirname(__FILE__)."/../../").'/';
    $locale_id = str_replace(array('wa-plugins/', '/'), array('', '_'), $locale_id);
    $include = array(
        $path.'/'.$app_id,
    );
} elseif (strpos($app_id, '/themes/')) {

    $path = realpath(dirname(__FILE__)."/../../")."/wa-apps/";
    $locale_id = str_replace('/themes/', '_', $locale_id);
    $include = array(
        $path.$app_id,
    );
} elseif (strpos($app_id, '/plugins/')) {

    $path = realpath(dirname(__FILE__)."/../../")."/wa-apps/";
    $locale_id = str_replace('/plugins/', '_', $locale_id);
    $include = array(
        $path.$app_id."/templates",
        $path.$app_id."/js",
        $path.$app_id."/lib",
    );
} else {
    $path = realpath(dirname(__FILE__)."/../../")."/wa-apps/";
    $locale_id = basename($locale_id);
    $include = array(
        $path.$app_id."/templates",
        $path.$app_id."/js",
        $path.$app_id."/lib",
        $path.$app_id."/themes",
    );
}

if (!file_exists($path)) {
    die("Application ".$app_id." does not exists\n");
}


$config = array(
    'project' => $app_id,
    'include' => ".+\.(html|(?<!min\.)js|php)",
    'path'    => $path.$app_id."/locale",
    'locales' => array()
);
$locales_path = realpath(dirname(__FILE__)."/../../")."/wa-config/locale.php";
if (file_exists($locales_path)) {
    $locales = include($locales_path);
} else {
    $locales = array('en_US', 'ru_RU',);
}

foreach ($locales as $l) {
    $config['locales'][$l] = $locale_id;
}

if (isset($argv[2]) && $argv[2] == 'verify') {
    $config['verify'] = 1;
}

if (in_array('debug', $argv)) {
    $config['debug'] = true;
}

$parser = new waGettextParser($config);

$parser->exec($include);
