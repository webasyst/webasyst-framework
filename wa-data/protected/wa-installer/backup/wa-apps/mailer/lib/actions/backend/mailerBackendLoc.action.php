<?php

/**
 * A list of localized strings to use in JS.
 */
class mailerBackendLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();

        // Application locale strings
        foreach(array(
            'Saved',
            'Cancel',
            'Total selected:',
            'not specified yet',
            'Image will be uploaded into',
            'Insert variable',
            'Save',
            'or',
            '%ds',
            '%dm',
            '%dh',
            'cancel',
            'Search results',
            'This plain-text version of your message is automatically created from HTML version and displayed if recipients have disabled HTML view in their email programs.',
            'Insert variable',
            'Close',
            'Apply',
        ) as $s) {
            $strings[$s] = _w($s);
        }

        $this->view->assign('strings', $strings ? $strings : new stdClass()); // stdClass is used to show {} instead of [] when there's no strings

        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}
