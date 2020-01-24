<?php

class waGettextParser
{
    /**
     * @var waLocaleParseEntityInterface
     */
    protected $entity;

    protected $options = [];

    protected $report = [];


    /**
     * waLocaleParse constructor.
     * @param waLocaleParseEntityInterface $entity
     */
    public function __construct(waLocaleParseEntityInterface $entity, $options = [])
    {
        $this->entity = $entity;
        $this->options = $options;
    }

    /**
     * TL;DR Why is waFiles used:
     * For tests, the largest "Shop" application was selected. Max memory usage: 1.2MB. Time: 1.6 sec;
     * With such loads and considering how rarely this script is used, the code was simplified by writing to the file 1 time.
     *
     * @throws waException
     */
    public function exec()
    {
        $this->createHtaccess();

        $locales = $this->entity->getLocales();
        $messages = $this->entity->getMessages();

        if (!$messages) {
            throw new waException('No messages found to save');
        }

        foreach ($locales as $locale) {
            // Need to clone to save different translations
            $clone_msg = $messages;
            $file = $this->getFile($locale);
            $gettext_data = (new waGettext($file, true))->getMessagesMetaPlurals();

            $clone_msg = $this->extendBySavedData($clone_msg, $gettext_data);
            $this->entity->preSave($clone_msg, $locale);

            // Get metadata for file
            $text = $this->getOldMeta($gettext_data['meta']);
            foreach ($clone_msg as $msgid => $msg_data) {
                $translates = ifset($msg_data, 'translate', '');
                $plural = ifset($msg_data, 'msgid_plural', false);
                $comments = '';

                // Add comments for debug
                if ($this->getOptions('debug')) {
                    $comments = ifset($msg_data, 'comments', '');
                }

                if ($plural !== false) {
                    $text .= $this->getPluralsText($msgid, $translates, $comments, $plural);
                } else {
                    $text .= $this->getStringsText($msgid, $translates, $comments);
                }
            }

            $save_result = $this->write($file, $text);
            $this->setReport($save_result, $locale, $clone_msg, $gettext_data['messages']);
        }
    }

    /**
     * @return array
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @param $new_msgs
     * @param $gettext_data
     * @return mixed
     */
    protected function extendBySavedData($new_msgs, $gettext_data)
    {
        $old_messages = [];

        /**
         * @var $translate string|string[]
         * If an array, then it contains plural forms
         */
        foreach ($gettext_data['messages'] as $saved_msgid => $translate) {
            // If the saved is not found in the new ones, we check whether it is a plural
            // Need to save plural form
            $saved_data = [
                'translate'    => $translate,
                'msgid_plural' => ifset($gettext_data, 'plurals', $saved_msgid, 'msgid_plural', false),
            ];

            if (!isset($new_msgs[$saved_msgid])) {
                // If the saved is not found in the new ones, add a comment
                $saved_data['comments'][] = 'Not found';
            } else {
                $saved_data['comments'] = $new_msgs[$saved_msgid]['comments'];

                // If old plural not find try find new plural
                if ($saved_data['msgid_plural'] === false) {
                    $saved_data['msgid_plural'] = ifset($new_msgs, $saved_msgid, 'msgid_plural', false);
                }
            }

            if ($saved_data['msgid_plural'] !== false && !is_array($saved_data['translate'])) {
                $saved_data['translate'] = (array)$saved_data['translate'];
            }

            $old_messages[$saved_msgid] = $saved_data;
            unset($new_msgs[$saved_msgid]);
        }

        $new_msgs = $old_messages + $new_msgs;

        return $new_msgs;
    }

    /**
     * Get strings text fo PO file
     * @example
     * #: /wa-apps/shop/themes/dummy/reviews.html:248
     * msgid "Adding"
     * msgstr ""
     *
     * @param $msgid
     * @param string $msgstr
     * @param null $comments
     * @return string
     * @throws waException
     */
    protected function getStringsText($msgid, $msgstr = '', $comments = null)
    {
        $result = "\n";
        $result .= $this->getComments($comments);
        $result .= "msgid ".$this->getStrings($msgid);
        $result .= "msgstr ".$this->getStrings($msgstr);

        return $result;
    }

    /**
     * Get plural text for PO file
     * @example
     *
     *  #: /wa-apps/shop/themes/dummy/reviews.html:264
     *  msgid "%d review for"
     *  msgid_plural "%d reviews for"
     *  msgstr[0] "%d отзыв о"
     *  msgstr[1] "%d отзыва о"
     *  msgstr[2] "%d отзывов о"
     *
     * @param $msgid                        e.g. '%d review for'
     * @param array $msgstr
     * @param null $comments
     * @param null|string|array $plural     plural form or a list of plural forms found (if more than one), e.g. '%d reviews for'
     * @return string
     * @throws waException
     */
    protected function getPluralsText($msgid, $msgstr = [], $comments = null, $plural = null)
    {
        $msg_str = (array)$msgstr;
        if (is_array($plural)) {
            // More than one plural form for single $msgid is not supported,
            // we only export a single message id to .po file
            $plural = reset($plural);
        }

        $result = "\n";
        $result .= $this->getComments($comments);
        $result .= "msgid ".$this->getStrings($msgid);
        $result .= "msgid_plural ".$this->getStrings($plural);

        // Create plural forms
        for ($i = 0; $i < 3; $i++) {
            $form = ifset($msg_str, $i, '');
            $result .= "msgstr[{$i}] ".$this->getStrings($form);
        }

        return $result;
    }

    /**
     * Create comments string
     *
     * @param null $comments
     * @return string
     */
    protected function getComments($comments = null)
    {
        $result = '';

        if (is_array($comments)) {
            $comments = implode("\n#: ", $comments);
        }

        if ($comments) {
            $result .= "#: {$comments}\n";
        }

        return $result;
    }

    /**
     * Get stings with escaped double quote
     * If the string is multi-line, it collects it into one
     *
     * @param $string
     * @return string
     * @throws waException
     */
    protected function getStrings($string)
    {
        if (!is_numeric($string) && !is_string($string)) {
            throw new waException('The input should be a scalar. Received: '.var_export($string, true));
        }

        $new_string = (string)str_replace('"', '\\"', $string);
        $result = '';

        // find multline
        // Example: "foo \nbar"
        if (preg_match('/[\n]/', $new_string)) {
            $parts = explode("\n", $new_string);

            //last not need line transfer
            $last = end($parts);
            array_pop($parts);

            // Create new string
            // Example msgid: "foo \n"
            //                "bar"
            foreach ($parts as $part) {
                $part .= '\n';
                $part = "\"{$part}\"\n";
                $result .= $part;
            }

            $result .= "\"{$last}\"\n";
        } else {
            $result .= "\"{$new_string}\"\n";
        }

        return $result;
    }

    /**
     * Get path to PO file
     * @param $locale
     * @return string
     */
    protected function getLocaleFilePath($locale)
    {
        return $this->entity->getLocalePath().'/'.$locale."/"."LC_MESSAGES"."/".$this->entity->getDomain().".po";
    }

    /**
     * Get path to PO file. Create if not exists
     * @param $locale
     * @return string
     * @throws waException
     */
    protected function getFile($locale)
    {
        $file_path = $this->getLocaleFilePath($locale);

        if (!file_exists($file_path)) {
            $create = $this->create($locale, $file_path);
            if (!$create) {
                throw new waException('Unable to create file on path: '.$file_path);
            }
        }

        return $file_path;
    }

    /**
     * Create new PO file
     *
     * @param $locale
     * @param $path
     * @return false|int
     * @throws waException
     */
    protected function create($locale, $path)
    {
        $meta = [
            'create'  => date("Y-m-d H:iO"),
            'project' => $this->entity->getProject(),
            'locale'  => $locale,
            'plural'  => $this->getPluralByLocale($locale),
        ];

        $text = $this->getMeta($meta);
        return $this->write($path, $text);
    }

    protected function getPluralByLocale($locale)
    {
        if ($locale == 'ru_RU') {
            $plural = 'Plural-Forms: nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));\n';
        } else {
            $plural = 'Plural-Forms: nplurals=2; plural=(n != 1);\n';
        }

        return $plural;
    }

    /**
     * @param $meta
     * @return string
     * @throws waException
     */
    protected function getMeta($meta)
    {
        $meta += [
            'revision'     => '',
            'mime'         => '1.0',
            'content_type' => 'text/plain; charset=utf-8',
            'encoding'     => '8bit',
            'charset'      => 'utf-8',
            'basepath'     => '.',
            'path0'        => '.',
            'path1'        => '.',
        ];

        foreach ($meta as $value) {
            if (!is_string($value)) {
                throw new waException("Meta value must be a string. Received: ".var_export($value, true));
            }
        }

        return <<<TEXT
msgid ""
msgstr ""
"Project-Id-Version: {$meta['project']}\\n"
"POT-Creation-Date: {$meta['create']}\\n"
"PO-Revision-Date: {$meta['revision']}\\n"
"Last-Translator:  {$meta['project']}\\n"
"Language-Team:  {$meta['project']}\\n"
"MIME-Version: {$meta['mime']}\\n"
"Content-Type: {$meta['content_type']}\\n"
"Content-Transfer-Encoding: {$meta['encoding']}\\n{$meta['plural']}"
"X-Poedit-Language: {$meta['locale']}\\n"
"X-Poedit-SourceCharset: {$meta['charset']}\\n"
"X-Poedit-Basepath: {$meta['charset']}\\n"
"X-Poedit-SearchPath-0: {$meta['path0']}\\n"
"X-Poedit-SearchPath-1: {$meta['path1']}\\n"

TEXT;
    }

    /**
     * Collect previous meta description
     * @param $meta
     * @return string
     */
    protected function getOldMeta($meta)
    {
        $text = <<<TEXT
msgid ""
msgstr ""

TEXT;
        foreach ($meta as $key => $value) {
            // open quote
            $text .= '"';
            $text .= $key.': ';

            if (is_string($value)) {
                $text .= $value;
            } elseif (is_array($value)) {
                $plural_string = '';
                foreach ($value as $plural => $plural_value) {
                    if (is_string($plural) && is_string($plural_value)) {
                        $plural_string .= $plural.'='.$plural_value.'; ';
                    }
                }
                $text .= trim($plural_string);
            }

            $text .= '\n';
            // close quote
            $text .= "\"\n";
        }

        return $text;
    }

    /**
     * Create apache file if not exists
     * @throws waException
     */
    protected function createHtaccess()
    {
        $path = $this->entity->getLocalePath().'/.htaccess';

        if (!file_exists($path)) {
            if (!$this->write($path, "Deny from all\n")) {
                throw new waException('Unable to create file on path: '.$path);
            };
        }
    }

    protected function getOptions($name)
    {
        return ifset($this->options, $name, null);
    }

    /**
     * @param $save_result
     * @param $locale
     * @param $messages
     * @param $old_messages
     */
    protected function setReport($save_result, $locale, $messages, $old_messages)
    {
        $messages = (array)$messages;
        $old_messages = (array)$old_messages;

        $report = [
            'result'  => $save_result,
            'locale'  => $locale,
            'total'   => count($messages),
            'updated' => count($old_messages),
        ];
        $report['new'] = $report['total'] - $report['updated'];

        $this->report[] = $report;
    }

    /**
     * waFiles Wrapper
     * @param $file
     * @param $text
     * @return bool
     */
    protected function write($file, $text)
    {
        return (bool)waFiles::write($file, $text);
    }

}
