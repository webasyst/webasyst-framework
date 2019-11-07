<?php

/**
 * Interface waLocaleParseEntityInterface
 */
interface waLocaleParseEntityInterface
{
    /**
     * List of words to save to .po file
     *
     * @return array
     */
    public function getMessages();

    /**
     * Returns the folder for localizations
     * @return string
     */
    public function getLocalePath();

    /**
     * Returns the domain under which localization will be stored
     * @return string
     */
    public function getDomain();

    /**
     * Returns a project for a PO file
     * @return string
     */
    public function getProject();

    /**
     * Returns which locales to save for an entity
     * @return string[]
     */
    public function getLocales();

    /**
     * Needed to delete words before saving
     * @param &$messages
     * @param $locale
     * @return bool
     */
    public function preSave(&$messages, $locale);
}