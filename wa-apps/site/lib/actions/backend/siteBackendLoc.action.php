<?php

/** 
 * A list of localized strings to use in JS. 
 */
class siteBackendLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();

        // Application locale strings
        foreach(array(
            'File URL', // _w('File URL')
            'Download',
        	'Rename',
        	'Move to folder',
            'Delete',
			'Delete file', // _w('Delete file')
           	'File',
          	'will be deleted without the ability to recover.', // _w('will be deleted without the ability to recover.')
          	'Saving...', // _w('Saving...')
          	'Saved', // _w('Saved')
          	'An error occurred while saving', // _w('An error occurred while saving')
          	'Unsaved changes will be lost if you leave this page now. Are you sure?', // _w('Unsaved changes will be lost if you leave this page now. Are you sure?')
          	'Image will be uploaded into', // _w('Image will be uploaded into')
        ) as $s) {
            $strings[$s] = _w($s);
        }

        $this->view->assign('strings', $strings ? $strings : new stdClass()); // stdClass is used to show {} instead of [] when there's no strings
        
        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}
