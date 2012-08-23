<?php

/**
 * A list of localized strings to use in JS.
 */
class photosBackendLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();

        // Application locale strings
        foreach(array(
            'Please select at least one album', //_w('Please select at least one album')
            'Please select at least one photo', //_w('Please select at least one photo')
            'Please select at least one tag', //_w('Please select at least one tag')
            'Please select at least two photos', //_w('Please select at least two photos')
            'Choose rate', //_w('Choose rate')
            'add description', //_w('add description')
            'Private photo', //_w('Private photo')
            'Are you sure to delete photo?', //_w('Are you sure to delete photo?')
            'Save', //_w('Save')
            'Photo downloaded by', //_w('Photo downloaded by')
            'Next →', //_w('Next →')
            '← Back', //_w('← Back')
            'Cancel', //_w('Cancel')
            'clear', //_w('clear')
            'Plugins', //_w('Plugins')
            'Settings', //_w('Settings')
            'add a tag', //_w('add a tag'),
            'Files with extensions *.gif, *.jpg, *.jpeg, *.png are allowed only.', //_w('Files with extensions *.gif, *.jpg, *.jpeg, *.png are allowed only.'),
            'Close', //_w('Close'),
            'Stop upload', //_w('Stop upload'),
            'Edit title...', //_w('Edit title...'),
            'Failed to upload. Most probably, there were not enough memory to create thumbnails.', //_w('Failed to upload. Most probably, there were not enough memory to create thumbnails.')
            'Upload photos (%d)',//_w('Upload photos (%d)')
            'Delete',//_w('Delete')
            'Click “Save” button below to apply this change.',//_w('Click “Save” button below to apply this change.')
            "You don't have sufficient access rights", //_w("You don't have sufficient access rights");
            'subtitle',//_w("subtitle");
            'Saving', //_w('Saving')
            'Saved', //_w('Saved')
            'Pages', // _w('Pages')
            'Empty result', //_w('Empty result')
            'This will reset all changes you applied to the image after upload, and will restore the image to its original. Are you sure?',//_w('This will reset all changes you applied to the image after upload, and will restore the image to its original. Are you sure?')
        ) as $s) {
            $strings[$s] = _w($s);
        }

        foreach(array(
            'kB',
            'MB',
            'GB',
            'TB',
            'PB',
            'EB',
        ) as $s) {
            $strings[$s] = _ws($s);
        }

        $this->view->assign('strings', $strings ? $strings : new stdClass()); // stdClass is used to show {} instead of [] when there's no strings

        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}
