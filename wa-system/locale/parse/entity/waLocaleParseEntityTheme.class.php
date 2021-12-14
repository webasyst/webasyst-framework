<?php

/**
 * Class waLocaleParseEntityTheme
 */
class waLocaleParseEntityTheme extends waLocaleParseEntity
{
    /**
     * @var string
     */
    protected $app_id = null;

    /**
     * @var string
     */
    protected $theme_id = null;

    /**
     * waLocaleParseEntityTheme constructor.
     * @param $params
     * @throws waException
     */
    public function __construct($params)
    {
        if (empty($params['app'])) {
            throw new waException('Invalid app');
        }

        if (empty($params['entity_id'])) {
            throw new waException('Invalid theme_id');
        }

        $this->app_id = $params['app'];
        $this->theme_id = $params['entity_id'];
    }

    /**
     * @return array
     */
    public function getOpenFunctionPatterns()
    {
        return [
            self::OPEN_SPRINTF_PATTERN
        ];
    }

    /**
     * @return array
     */
    public function getWebasystFunctionPatterns()
    {
        return [
            self::WEBASYST_PLUGIN_PATTERN,
        ];
    }

    /**
     * @return array
     * @throws waException
     */
    public function getSources()
    {
        $result = [
            $this->getThemePath()
        ];

        return $result;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->getAppID().'/themes/'.$this->getThemeID();
    }

    /**
     * @return string
     * @throws waException
     */
    public function getLocalePath()
    {
        return $this->getThemePath().'/locale';
    }

    /**
     * @return string
     * @throws waException
     */
    public function getThemePath()
    {
        return $this->getRootPath().'/wa-apps/'.$this->getAppID().'/themes/'.$this->getThemeID();
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->getAppID().'_themes_'.$this->getThemeID();
    }

    /**
     * @return string
     */
    protected function getAppID()
    {
        return $this->app_id;
    }

    /**
     * @return string
     */
    protected function getThemeID()
    {
        return $this->theme_id;
    }

    /**
     * @param $messages
     * @param $locale
     * @return bool
     * @throws waException
     */
    public function preSave(&$messages, $locale)
    {
        $app_messages = $this->getAppMessages($locale);
        $parent_messages = $this->getParentMessages($locale);

        foreach ($messages as $msgid => $message) {
            if (isset($app_messages[$msgid]) || isset($parent_messages[$msgid])) {
                unset($messages[$msgid]);
            }
        }

        return true;
    }

    /**
     * @param $locale
     * @return array
     * @throws waException
     */
    public function getParentMessages($locale)
    {
        $theme = new waTheme($this->getThemeID(), $this->getAppID());
        $parent = $theme->parent_theme;
        $parent_messages = [];

        if ($parent instanceof waTheme) {
            $parent_path = $parent->getPath();
            $file_path = $parent_path.'/locale/'.$locale.'/LC_MESSAGES/'.$parent->app_id.'_themes_'.$parent->id.'.po';

            if (file_exists($file_path)) {
                $gettext_data = $gettext_data = (new waGettext($file_path, true))->getMessagesMetaPlurals();
                $parent_messages = $gettext_data['messages'];
            }

        }

        return $parent_messages;
    }

    /**
     * @param $locale
     * @return array
     * @throws waException
     */
    public function getAppMessages($locale)
    {
        $file_path = wa($this->getAppID())->getConfig()->getAppPath("locale/{$locale}/LC_MESSAGES/{$this->getAppID()}.po");

        $app_messages = [];
        if (file_exists($file_path)) {
            $gettext_data = $gettext_data = (new waGettext($file_path, true))->getMessagesMetaPlurals();
            $app_messages = $gettext_data['messages'];
        }

        return $app_messages;
    }
}